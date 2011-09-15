<?php

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';

/**
 * Dummy class for testing only
 */
class ShopgatePluginCore {}
class ShopgateFramework {}
class ShopgateConfig {
    static public function setConfig(){}
    static public function getConfig(){
        return array(
		'api_url' => 'https://api.shopgate.com/shopgateway/api/',
		'customer_number' => 'THE_CUSTOMER_NUMBER',
		'shop_number' => 'THE_SHOP_NUMBER',
		'apikey' => 'THE_API_KEY',
		'server' => 'live',
		'plugin' => '',
		'plugin_language' => 'DE',
		'plugin_currency' => 'EUR',
		'plugin_root_dir' => "",
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
	);
    }
}
require_once getShopBasePath() . 'shopgate/plugins/plugin_oxid.inc.php';


class unit_marm_shopgate_shopgate_plugins_plugin_oxidTest extends OxidTestCase
{
    public function testLines()
    {
       $oObject = new ShopgatePlugin();
    }
}