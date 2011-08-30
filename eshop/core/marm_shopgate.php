<?php

class marm_shopgate
{

    /**
     * defines where shopgate framework placed.
     */
    const FRAMEWORK_DIR = 'shopgate';

    /**
     * stores created instance of framework object.
     * @var ShopgateFramework
     */
    protected $_oShopgateFramework = null

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
     * function loads framework by including it
     * @return void
     */
    public function init()
    {
        require_once $this->_getFrameworkFile();
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

        $this->oShopgateFramework = oxNew('ShopgateFramework');
        return $this->_oShopgateFramework;
    }

    /**
     * @return array
     */
    public function _getConfigForFramework()
    {
        return array(
            'customer_number' => "12345",
            'shop_number' => "54321",
            'apikey' => "asdf",
            'plugin' => "oxid",
            'enable_ping' => true,
            'enable_get_shop_info' => true,
            'enable_http_alert' => true,
            'enable_connect' => true,
            'enable_get_items_csv' => true,
            'enable_get_reviews_csv' => true,
            'enable_get_pages_csv' => true,
            'enable_get_log_file' => true,
            'enable_mobile_website' => true,
            'generate_items_csv_on_the_fly' => true,
            'max_attributes' => 50,
            'use_custom_error_handler' => false,
            'use_stock' => false,
            'shop_is_active' => false,
            'background_color' => "#333",
            'foreground_color' => "#3d3d3d",
        );
    }
}
