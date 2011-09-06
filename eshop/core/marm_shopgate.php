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
        'foreground_color' => 'input'
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
     * object initialization (build paths, load config) is called,
     * then new object is created.
     */
    public function __construct()
    {
        $this->init();
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
     * returns full path there framework class is defined
     * @return string
     */
    protected function _getFrameworkFile()
    {
        $sFile = $this->_getFrameworkDir()
                 . 'lib'
                 . DIRECTORY_SEPARATOR
                 . 'framework.php'
        ;
        return $sFile;
    }

    /**
     * returns full path there connect_api class is defined
     * @return string
     */
    protected function _getConnectFile()
    {
        $sFile = $this->_getFrameworkDir()
                 . 'lib'
                 . DIRECTORY_SEPARATOR
                 . 'connect_api.php'
        ;
        return $sFile;
    }

    /**
     * function loads framework by including it
     * @return void
     */
    public function init()
    {
        require_once $this->_getFrameworkFile();
        require_once $this->_getConnectFile();
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
        ShopgateConfig::setConfig($this->_getConfigForFramework());

        $this->_oShopgateFramework = oxNew('ShopgateFramework');
        return $this->_oShopgateFramework;
    }

    /**
     * returns array for framework filled with values from oxid configuration.
     * @return array
     */
    protected function _getConfigForFramework()
    {
        $aConfig = array();
        $oConfig = oxConfig::getInstance();
        foreach ($this->_aConfig as $sConfigKey => $sType) {
            if ($sValue = $oConfig->getShopConfVar('marm_shopgate_'.$sConfigKey)) {
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
        $aShopgateConfig = ShopgateConfig::getConfig();
        foreach ($this->_aConfig as $sConfigKey => $sType) {

            if ($sConfigKey == 'plugin')  continue;

            $sValue = $oOxidConfig->getShopConfVar('marm_shopgate_'.$sConfigKey);
            if (!$sValue) {
                $sValue = $aShopgateConfig[$sConfigKey];
            }
            $aConfig[] = array (
                'oxid_name'     => 'marm_shopgate_'.$sConfigKey,
                'shopgate_name' => $sConfigKey,
                'type' => $sType,
                'value' => $sValue
            );
        }
        return $aConfig;
    }
}
