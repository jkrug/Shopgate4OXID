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
    protected function tearDown()
    {
        modConfig::$unitMOD = null;
        oxTestModules::cleanUp();
    }
    
    public function test_startup()
    {
        $oConfigMock = $this->getMock(
            'oxConfig',
            array(
                'getActShopCurrencyObject',
                'getConfigParam'
            )
        );
        $oConfigMock
            ->expects($this->once())
            ->method('getActShopCurrencyObject')
        ;
        $oConfigMock
            ->expects($this->once())
            ->method('getConfigParam')
        ;
        modConfig::$unitMOD = $oConfigMock;
        $oPlugin = new ShopgatePlugin();
        $this->assertTrue($oPlugin->startup());
    }

    public function test__getArticleBase()
    {
        $oArticleMock = $this->getMock(
            'oxArticle',
            array(
                'setSkipAbPrice',
                'setLoadParentData',
                'setNoVariantLoading'
            )
        );
        $oArticleMock
            ->expects($this->once())
            ->method('setSkipAbPrice')
            ->with(true)
        ;
        $oArticleMock
            ->expects($this->once())
            ->method('setLoadParentData')
            ->with(false)
        ;
        $oArticleMock
            ->expects($this->once())
            ->method('setNoVariantLoading')
            ->with(true)
        ;
        oxTestModules::addModuleObject('oxArticle', $oArticleMock);
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oResult = $oPlugin->_getArticleBase();
        $this->assertTrue($oResult instanceof oxArticle);

    }

    public function test__getArticleSQL()
    {
        $sViewName = 'customViewName';
        $sSelectFields = 'custom_selectFields';
        $sSelectWhere = 'customwhere';
        $oArticleMock = $this->getMock(
            'oxarticle',
            array(
                'getViewName',
                'getSelectFields',
                'getSqlActiveSnippet'
            )
        );
        $oArticleMock
            ->expects($this->once())
            ->method('getViewName')
            ->will($this->returnValue($sViewName))
        ;
        $oArticleMock
            ->expects($this->once())
            ->method('getSelectFields')
            ->will($this->returnValue($sSelectFields))
        ;
        $oArticleMock
            ->expects($this->once())
            ->method('getSqlActiveSnippet')
            ->will($this->returnValue($sSelectWhere))
        ;
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sResult = $oPlugin->_getArticleSQL($oArticleMock);
        $this->assertTrue(strpos($sResult, $sViewName) !== false);
        $this->assertTrue(strpos($sResult, $sSelectFields) !== false);
        $this->assertTrue(strpos($sResult, $sSelectWhere) !== false);
    }

    public function test_createItemsCsv()
    {
        $sTestSql = 'SELECT * FROM oxarticles LIMIT 2';
        $oxArticle = oxNew('oxArticle');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getArticleBase',
                '_getArticleSQL',
                'buildDefaultRow',
                '_loadFieldsForArticle',
                'addItem'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getArticleBase')
            ->will($this->returnValue($oxArticle))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_getArticleSQL')
            ->with($oxArticle)
            ->will($this->returnValue($sTestSql))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('buildDefaultRow')
            ->will($this->returnValue(array()))
        ;
        $oPlugin
            ->expects($this->exactly(2))
            ->method('_loadFieldsForArticle')
        ;
        $oPlugin
            ->expects($this->exactly(2))
            ->method('addItem')
        ;
        $oPlugin->createItemsCsv();

    }

    public function test__loadFieldsForArticle()
    {
        $aChain = array(
            '_loadRequiredFieldsForArticle',
            '_loadAdditionalFieldsForArticle',
            '_loadSelectionListForArticle',
            '_loadVariantsInfoForArticle',
            '_loadPersParamForArticle'
        );
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            $aChain
        );
        $oArticle = oxNew('oxArticle');
        $iChainNumber = 1;
        $aOutputArray = array();
        foreach ($aChain as $sMethod) {
            $aInputArray = $aOutputArray;
            $aOutputArray[] = $iChainNumber++;
            $oPlugin
                ->expects($this->once())
                ->method($sMethod)
                ->with($aInputArray, $oArticle)
                ->will($this->returnValue($aOutputArray))
            ;
        }

        $this->assertEquals($aOutputArray, $oPlugin->_loadFieldsForArticle(array(), $oArticle));
    }

    public function test__formatPrice()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertEquals(1.11, $oPlugin->_formatPrice(1.1111111));
        $this->assertEquals(2.12, $oPlugin->_formatPrice(2.1159));
        $this->assertEquals(3.00, $oPlugin->_formatPrice(3));
    }

    public function test__loadIsAvailableForArticle()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oArticleMock = $this->getMock(
            'oxArticle',
            array(
                'isBuyable'
            )
        );
        $oArticleMock
            ->expects($this->at(0))
            ->method('isBuyable')
            ->will($this->returnValue(true))
        ;
        $oArticleMock
            ->expects($this->at(0))
            ->method('isBuyable')
            ->will($this->returnValue(false))
        ;
        $aItem = array();
        $aItem = $oPlugin->_loadIsAvailableForArticle($aItem, $oArticleMock);
        $this->assertEquals('1', $aItem['is_available']);
        $aItem = $oPlugin->_loadIsAvailableForArticle($aItem, $oArticleMock);
        $this->assertEquals('0', $aItem['is_available']);
    }
}