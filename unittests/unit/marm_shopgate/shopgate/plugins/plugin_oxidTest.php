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
                '_getMainItemLoaders',
                '_executeLoaders',
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
            ->expects($this->once())
            ->method('_getMainItemLoaders')
            ->will($this->returnValue(array()))
        ;
        $oPlugin
            ->expects($this->exactly(2))
            ->method('_executeLoaders')
        ;
        $oPlugin
            ->expects($this->exactly(2))
            ->method('addItem')
        ;
        $oPlugin->createItemsCsv();

    }

    public function test__executeLoaders()
    {
        $aTestMethods = array(
            'test1',
            'test2'
        );
        $oArticle = oxNew('oxArticle');
        $oPluginMock = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            $aTestMethods
        );
        $iChainNumber = 0;
        $aInputArray = array();
        $aOutputArray = array();
        $aOutputArray[] = $iChainNumber++;
        $oPluginMock
            ->expects($this->once())
            ->method('test1')
            ->with($aInputArray, $oArticle)
            ->will($this->returnValue($aOutputArray))
        ;
        $aInputArray = $aOutputArray;
        $aOutputArray[] = $iChainNumber++;
        $oPluginMock
            ->expects($this->once())
            ->method('test2')
            ->with($aInputArray, $oArticle)
            ->will($this->returnValue($aOutputArray))
        ;
        $aItems = $oPluginMock->_executeLoaders($aTestMethods, array(), $oArticle);
        $this->assertType('array', $aItems);
        $this->assertEquals($aOutputArray, $aItems);

    }

    public function test__getMainItemLoaders()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aResult = $oPlugin->_getMainItemLoaders();
        $this->assertContains('_loadRequiredFieldsForArticle', $aResult);
        $this->assertContains('_loadAdditionalFieldsForArticle', $aResult);
        $this->assertContains('_loadSelectionListForArticle', $aResult);
        $this->assertContains('_loadVariantsInfoForArticle', $aResult);
        $this->assertContains('_loadPersParamForArticle', $aResult);
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
        $aItem = $oPlugin->_loadArticleExport_is_available($aItem, $oArticleMock);
        $this->assertEquals('1', $aItem['is_available']);
        $aItem = $oPlugin->_loadArticleExport_is_available($aItem, $oArticleMock);
        $this->assertEquals('0', $aItem['is_available']);
    }

    public function test__getRequiredItemFieldLoaders()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aResult = $oPlugin->_getRequiredItemFieldLoaders();
        $this->assertContains('_loadArticleExport_item_number', $aResult);
        $this->assertContains('_loadArticleExport_item_name', $aResult);
        $this->assertContains('_loadArticleExport_unit_amount', $aResult);
        $this->assertContains('_loadArticleExport_description', $aResult);
        $this->assertContains('_loadArticleExport_url_images', $aResult);
        $this->assertContains('_loadArticleExport_categories', $aResult);
        $this->assertContains('_loadArticleExport_is_available', $aResult);
        $this->assertContains('_loadArticleExport_available_text', $aResult);
        $this->assertContains('_loadArticleExport_manufacturer', $aResult);
        $this->assertContains('_loadArticleExport_url_deeplink', $aResult);
    }

    public function test__loadRequiredFieldsForArticle()
    {
        $aOutput = array('ok');
        $aTestArticle = oxNew('oxArticle');
        $aTestLoaders = array('test1', 'test2', 'test3');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_executeLoaders',
                '_getRequiredItemFieldLoaders'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getRequiredItemFieldLoaders')
            ->will($this->returnValue($aTestLoaders))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_executeLoaders')
            ->with($aTestLoaders, array(), $aTestArticle)
            ->will($this->returnValue($aOutput))
        ;
        $this->assertEquals($aOutput, $oPlugin->_loadRequiredFieldsForArticle(array(), $aTestArticle));
    }

    public function test__loadArticleExport_item_number()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sArtNum = 'a123_1-2';
        $oTestArticle->oxarticles__oxartnum = new oxField($sArtNum, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_item_number(array(), $oTestArticle);
        $this->assertEquals($sArtNum, $aItem['item_number']);
    }

    public function test__loadArticleExport_item_name()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sTitle = 'a123_1-2';
        $oTestArticle->oxarticles__oxtitle = new oxField($sTitle, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_item_name(array(), $oTestArticle);
        $this->assertEquals($sTitle, $aItem['item_name']);
    }

    public function test__loadArticleExport_unit_amount()
    {
        /** @var $oTestArticle oxArticle */
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        /** @var $oPrice oxprice */
        $oPrice = oxNew('oxPrice');
        $oPrice->setPrice(12.345);
        $oTestArticle->setPrice($oPrice);
        $aItem = $oPlugin->_loadArticleExport_unit_amount(array(), $oTestArticle);
        $this->assertEquals(12.35, $aItem['unit_amount']);
    }

    public function test__loadArticleExport_description()
    {
        $sLongDesc = "some text here";
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getLongDesc')
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getLongDesc')
            ->will($this->returnValue($sLongDesc))
        ;
        $aItem = $oPlugin->_loadArticleExport_description(array(), $oTestArticle);
        $this->assertEquals($sLongDesc, $aItem['description']);
    }

    public function test__loadArticleExport_url_deeplink()
    {
        $sLink = "http://testing.ln/";
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getLink')
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getLink')
            ->will($this->returnValue($sLink))
        ;
        $aItem = $oPlugin->_loadArticleExport_url_deeplink(array(), $oTestArticle);
        $this->assertEquals($sLink, $aItem['url_deeplink']);
    }

    public function test__loadArticleExport_url_images()
    {
        $aPicGal0 = array('ZoomPic' => true,  'ZoomPics' => array(array('file'=>'img1'), array('file'=>'img2')));
        $aPicGal1 = array('ZoomPic' => true,  'ZoomPics' => array(array('file'=>'img3')));
        $aPicGal2 = array('ZoomPic' => false, 'ActPic'=>'img4');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getPictureGallery')
        );
        $oTestArticle
            ->expects($this->at(0))
            ->method('getPictureGallery')
            ->will($this->returnValue($aPicGal0))
        ;
        $oTestArticle
            ->expects($this->at(1))
            ->method('getPictureGallery')
            ->will($this->returnValue($aPicGal1))
        ;
        $oTestArticle
            ->expects($this->at(2))
            ->method('getPictureGallery')
            ->will($this->returnValue($aPicGal2))
        ;

        $aItem = $oPlugin->_loadArticleExport_url_images(array(), $oTestArticle);
        $this->assertEquals('img1||img2', $aItem['urls_images']);
        $aItem = $oPlugin->_loadArticleExport_url_images(array(), $oTestArticle);
        $this->assertEquals('img3', $aItem['urls_images']);
        $aItem = $oPlugin->_loadArticleExport_url_images(array(), $oTestArticle);
        $this->assertEquals('img4', $aItem['urls_images']);
    }

    public function test__loadArticleExport_categories()
    {
        $aArticleCats = array('cat1', 'cat3', 'cat4');
        $aCatPaths = array(
            'cat1' => 'main=>sub1',
            'cat2' => 'main2=>sub2',
            'cat3' => 'main3=>sub3=>sub4'
        );
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getCategoriesPath'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getCategoriesPath')
            ->will($this->returnValue($aCatPaths))
        ;
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getCategoryIds')
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getCategoryIds')
            ->will($this->returnValue($aArticleCats))
        ;
        $aItem = $oPlugin->_loadArticleExport_categories(array(), $oTestArticle);
        $this->assertEquals('main=>sub1||main3=>sub3=>sub4', $aItem['categories']);
    }

    public function test__getCategoriesPath()
    {
        $aCats0 = array(
            array(
                'OXID' => 'c1',
                'OXTITLE' => 'cat1',
                'OXPARENTID' => 'oxrootid'
            ),
            array(
                'OXID' => 'c2',
                'OXTITLE' => 'cat2',
                'OXPARENTID' => 'c1'
            ),
            array(
                'OXID' => 'c3',
                'OXTITLE' => 'cat3',
                'OXPARENTID' => 'c1'
            ),
            array(
                'OXID' => 'c4',
                'OXTITLE' => 'cat4',
                'OXPARENTID' => 'c2'
            ),
            array(
                'OXID' => 'c5',
                'OXTITLE' => 'cat5',
                'OXPARENTID' => 'oxrootid'
            )
        );
        $aCats1 = array();
        $aExpected0 = array(
            'c1' => 'cat1',
            'c2' => 'cat1=>cat2',
            'c3' => 'cat1=>cat3',
            'c4' => 'cat1=>cat2=>cat4',
            'c5' => 'cat5',
        );
        $aExpected1 = array();
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getCategoriesPathFromDB'
            )
        );
        $oPlugin
            ->expects($this->at(0))
            ->method('_getCategoriesPathFromDB')
            ->will($this->returnValue($aCats0))
        ;
        $oPlugin
            ->expects($this->at(1))
            ->method('_getCategoriesPathFromDB')
            ->will($this->returnValue($aCats1))
        ;
        $this->assertEquals($aExpected0, $oPlugin->_getCategoriesPath());
        // caching
        $this->assertEquals($aExpected0, $oPlugin->_getCategoriesPath());
        // reset caching
        $this->assertEquals($aExpected1, $oPlugin->_getCategoriesPath(true));
    }

    public function test__getCategoriesPathFromDB()
    {
        $sLangTag = '_2';
        $aExpectedOutput = array('this', 'is', 'mock');
        $sTitleField = 'OXTITLE'.$sLangTag;
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getLanguageTagForTable',
                '_dbGetAll'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getLanguageTagForTable')
            ->will($this->returnValue($sLangTag))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_dbGetAll')
            ->with($this->stringContains($sTitleField))
            ->will($this->returnValue($aExpectedOutput))
        ;
        $this->assertEquals($aExpectedOutput, $oPlugin->_getCategoriesPathFromDB());
    }

    public function test__dbGetAll()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sSQL = 'SELECT ? AS column_name';
        $this->assertEquals(array(array('column_name'=>'value')), $oPlugin->_dbGetAll($sSQL, array('value')));

        $sSQL = 'error';
        $this->assertEquals(array(), $oPlugin->_dbGetAll($sSQL));
    }

    public function test__loadArticleExport_manufacturer()
    {
        $aManufacturers = array(
            'man1' => 'acme',
            'man2' => 'sony'
        );
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getManufacturers'
            )
        );
        $oPlugin
            ->expects($this->exactly(2))
            ->method('_getManufacturers')
            ->will($this->returnValue($aManufacturers))
        ;
        $oTestArticle = oxNew('oxArticle');
        // not set or empty
        $aItem = $oPlugin->_loadArticleExport_manufacturer(array(), $oTestArticle);
        $this->assertEquals('', $aItem['manufacturer']);
        // not existing
        $oTestArticle->oxarticles__oxmanufacturerid = new oxField('nonexisting');
        $aItem = $oPlugin->_loadArticleExport_manufacturer(array(), $oTestArticle);
        $this->assertEquals('', $aItem['manufacturer']);

        // normal
        $oTestArticle->oxarticles__oxmanufacturerid = new oxField('man1');
        $aItem = $oPlugin->_loadArticleExport_manufacturer(array(), $oTestArticle);
        $this->assertEquals($aManufacturers['man1'], $aItem['manufacturer']);

    }
}