<?php

class marm_shopgate
{
    /**
     * information about how shopgate config variables
     * are editable in oxid admin GUI
     * @var array
     */
    protected $_aConfig = array(
        'customer_number' => 'input',
        'shop_number' => 'input',
        'apikey' => 'input',
        'plugin' => false,
        'enable_ping' => 'checkbox',
        'enable_get_shop_info' => 'checkbox',
        'enable_http_alert' => 'checkbox',
        'enable_connect' => 'checkbox',
        'enable_get_items_csv' => 'checkbox',
        'enable_get_reviews_csv' => 'checkbox',
        'enable_get_pages_csv' => 'checkbox',
        'enable_get_log_file' => 'checkbox',
        'enable_mobile_website' => 'checkbox',
        'generate_items_csv_on_the_fly' => 'checkbox',
        'max_attributes' => 'input',
        'use_custom_error_handler' => 'checkbox',
        'use_stock' => 'checkbox',
        'shop_is_active' => 'checkbox',
        'background_color' => 'input',
        'foreground_color' => 'input',
        'server' => 'input'
    );

    /**
     * contains array of files which will be included from library
     * to get framework working
     * @var array
     */
    protected $_aFilesToInclude = array(
        'framework.php',
        'connect_api.php',
        'core_api.php',
        'order_api.php'
    );
    /**
     * defines where shopgate framework placed.
     */
    const FRAMEWORK_DIR = 'shopgate';

    /**
     * stores created instance of framework object.
     * @var ShopgateFramework
     */
    protected $_oShopgateFramework = null;

    /**
     * marm_shopgate class instance.
     *
     * @var marm_shopgate
     */
    private static $_instance = null;

    /**
     * returns marm_shopgate object
     *
     * @return marm_shopgate
     */
    public static function getInstance()
    {
        if ( !(self::$_instance instanceof marm_shopgate) ) {
            self::$_instance = oxNew( 'marm_shopgate' );
        }
        return self::$_instance;
    }

    /**
     * replace given object to the class instance.
     * USED ONLY FOR PHPUNIT
     * @param marm_shopgate $oNewInstance
     * @return marm_shopgate
     */
    public static function replaceInstance(marm_shopgate $oNewInstance)
    {
        $oOldInstance = self::$_instance;
        self::$_instance = $oNewInstance;
        return $oOldInstance;
    }

    /**
     * returns full path, where framework is placed.
     * @return string
     */
    protected function _getFrameworkDir()
    {
        $sDir = oxConfig::getInstance()->getConfigParam( 'sShopDir' )
                . DIRECTORY_SEPARATOR
                . self::FRAMEWORK_DIR
                . DIRECTORY_SEPARATOR
        ;
        return $sDir;
    }

    /**
     * returns path of framework library
     * @return string
     */
    protected function _getLibraryDir()
    {
        return $this->_getFrameworkDir() . 'lib' . DIRECTORY_SEPARATOR;
    }

    /**
     * returns array of file names from shopgate framework library
     * that has to be inlcuded
     * @return array
     */
    protected function _getFilesToInclude()
    {
        return $this->_aFilesToInclude;
    }

    /**
     * function loads framework by including it
     * @return void
     */
    public function init()
    {
        $sLibraryDir = $this->_getLibraryDir();
        foreach ($this->_getFilesToInclude() as $sFile) {
            $sFile = $sLibraryDir . $sFile;
            if (file_exists($sFile)) {
                require_once $sFile;
            }
        }
    }


    /**
     * returns ShopgateFramework object,
     * saves it internally,
     * resets instance if $blReset = true
     *
     * @param bool $blReset
     * @return ShopgateFramework
     */
    public function getFramework($blReset = false)
    {
        if ($this->_oShopgateFramework !== null && !$blReset) {
            return $this->_oShopgateFramework;
        }
        $this->init();
        ShopgateConfig::setConfig($this->_getConfigForFramework());

        $this->_oShopgateFramework = oxNew('ShopgateFramework');
        return $this->_oShopgateFramework;
    }

    /**
     * returns array shopgate config name and edit type in oxid (checkbox, input)
     * @return array
     */
    protected function _getConfig()
    {
        return $this->_aConfig;
    }

    /**
     * returns array for framework filled with values from oxid configuration.
     * @return array
     */
    protected function _getConfigForFramework()
    {
        $aConfig = array();
        $oConfig = oxConfig::getInstance();
        foreach ($this->_getConfig() as $sConfigKey => $sType) {
            $sValue = $oConfig->getConfigParam($this->getOxidConfigKey($sConfigKey));
            if ($sValue !== null) {
                $aConfig[$sConfigKey] = $sValue;
            }
        }
        $aConfig['plugin'] = 'oxid';
        return $aConfig;
    }

    /**
     * returns shopgate config array with information
     * how to display it in format:
     * array(
     *   [oxid_name] => marm_shopgate_customer_number
     *   [shopgate_name] => customer_number
     *   [type] => checkbox|input
     *   [value] => 1234567890
     * )
     * @return array
     */
    public function getConfigForAdminGui()
    {
        $aConfig = array();
        $oOxidConfig = oxConfig::getInstance();
        $this->init();
        $aShopgateConfig = ShopgateConfig::getConfig();
        foreach ($this->_getConfig() as $sConfigKey => $sType) {

            if ($sConfigKey == 'plugin')  continue;
            $sOxidConfigKey = $this->getOxidConfigKey($sConfigKey);
            $sValue = $oOxidConfig->getConfigParam($sOxidConfigKey);
            if ($sValue === null) {
                $sValue = $aShopgateConfig[$sConfigKey];
            }
            $aConfig[$sConfigKey] = array (
                'oxid_name'     => $sOxidConfigKey,
                'shopgate_name' => $sConfigKey,
                'type' => $sType,
                'value' => $sValue
            );
        }
        return $aConfig;
    }

    /**
     * will generate key name on which oxid will 
     * @param $sShopgateConfigKey
     * @return string
     */
    public function getOxidConfigKey($sShopgateConfigKey)
    {
        $sShopgateConfigKey = strtolower($sShopgateConfigKey);
        $sHash = md5($sShopgateConfigKey);
        $sStart = substr($sHash, 0, 3);
        $sEnd = substr($sHash, -3);
        return 'marm_shopgate_'.$sStart.$sEnd;
    }

    /**
     * outputs HTML SCRIPT tag for shopgate mobile javascript redirect
     * @return string
     */
    public function getMobileSnippet()
    {
        $sSnippet = '';
        $oOxidConfig = oxConfig::getInstance();
        if ( $oOxidConfig->getConfigParam($this->getOxidConfigKey('enable_mobile_website')) ) {
            $iShopNumber = $oOxidConfig->getConfigParam($this->getOxidConfigKey('shop_number'));
            $sSnippet  = '<script type="text/javascript">'."\n";
            $sSnippet .= 'var _shopgate = {}; '."\n";
            $sSnippet .= '_shopgate.shop_number = "'.$iShopNumber.'"; '."\n";
            $sSnippet .= '_shopgate.host = (("https:" == document.location.protocol) ? "';
            $sSnippet .= 'https://static-ssl.shopgate.com" : "http://static.shopgate.com"); '."\n";
            $sSnippet .= 'document.write(unescape("%3Cscript src=\'" + _shopgate.host + ';
            $sSnippet .= '"/mobile_header/" + _shopgate.shop_number + ".js\' ';
            $sSnippet .= 'type=\'text/javascript\' %3E%3C/script%3E")); '."\n";
            $sSnippet .= '</script>';
        }
        return $sSnippet;
    }
}
