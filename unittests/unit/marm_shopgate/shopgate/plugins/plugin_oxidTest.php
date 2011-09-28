<?php

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';

/**
 * Dummy class for testing only
 */
class ShopgateConnectException extends Exception
{
    const INVALID_USERNAME_OR_PASSWORD = 100;
}
/**
 * class for dynamic string value, used in mock result values.
 */
class StringChanger
{
    protected $_sString = '';
    public function __construct($sString)
    {
        $this->setString($sString);
    }
    public function setString($sString)
    {
        $this->_sString = $sString;
    }
    public function __toString()
    {
        return $this->_sString;
    }
}
class ShopgateFrameworkException extends Exception{}
class ShopgateShopCustomer {
    const MALE = 0;
    const FEMALE = 1;
}
class ShopgatePluginCore {}
class ShopgateOrder{}
class ShopgateOrderAddress{}
class ShopgateOrderItem{}
class ShopgateOrderItemOption{}
class ShopgateOrderApi{}
class ShopgateFramework {}
class ShopgateConfig {
    public static $aLastSetConfig = null;
    static public function setConfig(){ self::$aLastSetConfig = func_get_args(); }
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
    protected $_blResetOxConfig  = false;
    protected $_blResetInstances = false;
    protected $_blResetModules   = false;

    protected function tearDown()
    {
        if ($this->_blResetOxConfig) {
            $this->_blResetOxConfig = false;
            modConfig::$unitMOD = null;
        }
        if ($this->_blResetInstances) {
            $this->_blResetInstances = false;
            modInstances::cleanup();
        }
        if ($this->_blResetModules) {
            $this->_blResetModules = false;
            oxTestModules::cleanUp();
        }
    }
    
    public function test_startup()
    {
        $oConfigMock = $this->getMock(
            'oxConfig',
            array(
                'getConfigParam'
            )
        );
        $oConfigMock
            ->expects($this->once())
            ->method('getConfigParam')
        ;
        $this->_blResetOxConfig = true;
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
        $this->_blResetModules = true;
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

    public function test_getDefaultRowForCreateItemsCsv()
    {
        $aResult1 = array('new version');
        $aResult2 = array('old version');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestPlugin1 = $this->getMock(
            'stdClass',
            array(
                'buildDefaultProductRow'
            )
        );
        $oTestPlugin1
            ->expects($this->once())
            ->method('buildDefaultProductRow')
            ->will($this->returnValue($aResult1))
        ;
        $oTestPlugin2 = $this->getMock(
            'stdClass',
            array(
                'buildDefaultRow'
            )
        );
        $oTestPlugin2
            ->expects($this->once())
            ->method('buildDefaultRow')
            ->will($this->returnValue($aResult2))
        ;

        $this->assertEquals(array(), $oPlugin->getDefaultRowForCreateItemsCsv(new stdClass()));
        $this->assertEquals($aResult1, $oPlugin->getDefaultRowForCreateItemsCsv($oTestPlugin1));
        $this->assertEquals($aResult2, $oPlugin->getDefaultRowForCreateItemsCsv($oTestPlugin2));

    }

    public function test_createItemsCsv()
    {
        $sTestSql = 'SELECT OXID FROM oxarticles LIMIT 2';
        $oxArticle = oxNew('oxArticle');
        $oxArticle->setSkipAssign(true);
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getArticleBase',
                '_getArticleSQL',
                'getDefaultRowForCreateItemsCsv',
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
            ->method('getDefaultRowForCreateItemsCsv')
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

    public function test__loadArticleExport_description_for_older_then_450()
    {
        $sLongDesc = "some text here";
        $oTestArticle = $this->getMock( 'stdClass' );
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getArticleLongDesc'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getArticleLongDesc')
            ->with($oTestArticle)
            ->will($this->returnValue($sLongDesc))
        ;
        $aItem = $oPlugin->_loadArticleExport_description(array(), $oTestArticle);
        $this->assertEquals($sLongDesc, $aItem['description']);
    }

    public function test__getArticleLongDesc()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oLongDesc = new stdClass();
        $oLongDesc->rawValue = 'raw_value';
        $oLongDesc->Value = 'simple_value';
        $oTestArticle = $this->getMock(
            'oxArticle',
            array(
                'getArticleLongDesc',
                'getId',
                'getLanguage'
            )
        );
        $oTestArticle
            ->expects($this->exactly(2))
            ->method('getArticleLongDesc')
            ->will($this->returnValue($oLongDesc))
        ;
        $oTestArticle
            ->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('id1'))
        ;
        $oTestArticle
            ->expects($this->exactly(2))
            ->method('getLanguage')
            ->will($this->returnValue('lang2'))
        ;
        $oOxUtilsViewMock = $this->getMock(
            'oxUtilsView',
            array(
                'parseThroughSmarty'
            )
        );
        $oOxUtilsViewMock
            ->expects($this->at(0))
            ->method('parseThroughSmarty')
            ->with($oLongDesc->rawValue, 'id1lang2')
            ->will($this->returnValue('OK1'))
        ;
        $oOxUtilsViewMock
            ->expects($this->at(1))
            ->method('parseThroughSmarty')
            ->with($oLongDesc->value, 'id1lang2')
            ->will($this->returnValue('OK2'))
        ;
        $this->_blResetInstances = true;
        modInstances::addMod('oxUtilsView', $oOxUtilsViewMock);
        
        $this->assertEquals('OK1', $oPlugin->_getArticleLongDesc($oTestArticle));
        $oLongDesc->rawValue = null;
        $this->assertEquals('OK2', $oPlugin->_getArticleLongDesc($oTestArticle));
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

    public function test__dbGetOne()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sSQL = 'SELECT ? AS column_name';
        $this->assertEquals('value', $oPlugin->_dbGetOne($sSQL, array('value')));

        $sSQL = 'error';
        $this->assertNull($oPlugin->_dbGetOne($sSQL));
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

    public function test__getManufacturers()
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
            ->expects($this->exactly(2))
            ->method('_getLanguageTagForTable')
            ->will($this->returnValue($sLangTag))
        ;
        $oPlugin
            ->expects($this->exactly(2))
            ->method('_dbGetAll')
            ->with($this->stringContains('oxmanufacturers'))
            ->will($this->returnValue($aExpectedOutput))
        ;
        $this->assertEquals($aExpectedOutput, $oPlugin->_getManufacturers());
        $this->assertEquals($aExpectedOutput, $oPlugin->_getManufacturers());
        $this->assertEquals($aExpectedOutput, $oPlugin->_getManufacturers(true));
    }

    public function test__loadArticleExport_available_text()
    {
        $sIdent1 = 'DAY';
        $sTransResult1 = 'DIENA';
        $sIdent2 = 'WEEK';
        $sTransResult2 = 'SAVAITES';
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getTranslation'
            )
        );
        $oPlugin
            ->expects($this->at(0))
            ->method('_getTranslation')
            ->with($this->identicalTo($sIdent1), $this->anything())
            ->will($this->returnValue($sTransResult1))
        ;
        $oPlugin
            ->expects($this->at(1))
            ->method('_getTranslation')
            ->with($this->identicalTo($sIdent2.'S'), $this->anything())
            ->will($this->returnValue($sTransResult2))
        ;
        $oTestArticle = oxNew('oxArticle');
        $oTestArticle->oxarticles__oxmindeltime = new oxField(1, oxField::T_RAW);
        $oTestArticle->oxarticles__oxmaxdeltime = new oxField(1, oxField::T_RAW);
        $oTestArticle->oxarticles__oxdeltimeunit = new oxField($sIdent1, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_available_text(array(), $oTestArticle);
        $this->assertEquals('1 '.$sTransResult1, $aItem['available_text']);

        $oTestArticle->oxarticles__oxmindeltime = new oxField(2, oxField::T_RAW);
        $oTestArticle->oxarticles__oxmaxdeltime = new oxField(5, oxField::T_RAW);
        $oTestArticle->oxarticles__oxdeltimeunit = new oxField($sIdent2, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_available_text(array(), $oTestArticle);
        $this->assertEquals('2 - 5 '.$sTransResult2, $aItem['available_text']);
    }

    public function test__getAdditionalItemFieldLoaders()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aResult = $oPlugin->_getAdditionalItemFieldLoaders();
        $this->assertContains('_loadArticleExport_properties', $aResult);
        $this->assertContains('_loadArticleExport_manufacturer_item_number', $aResult);
        $this->assertContains('_loadArticleExport_currency', $aResult);
        $this->assertContains('_loadArticleExport_tax_percent', $aResult);
        $this->assertContains('_loadArticleExport_msrp', $aResult);
        $this->assertContains('_loadArticleExport_basic_price', $aResult);
        $this->assertContains('_loadArticleExport_use_stock', $aResult);
        $this->assertContains('_loadArticleExport_stock_quantity', $aResult);
        $this->assertContains('_loadArticleExport_ean', $aResult);
        $this->assertContains('_loadArticleExport_last_update', $aResult);
        $this->assertContains('_loadArticleExport_tags', $aResult);
        $this->assertContains('_loadArticleExport_marketplace', $aResult);
        $this->assertContains('_loadArticleExport_weight', $aResult);
        $this->assertContains('_loadArticleExport_is_free_shipping', $aResult);
        $this->assertContains('_loadArticleExport_block_pricing', $aResult);
    }

    public function test__loadAdditionalFieldsForArticle()
    {
        $aOutput = array('ok');
        $aTestArticle = oxNew('oxArticle');
        $aTestLoaders = array('test4', 'test5', 'test6');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_executeLoaders',
                '_getAdditionalItemFieldLoaders'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getAdditionalItemFieldLoaders')
            ->will($this->returnValue($aTestLoaders))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_executeLoaders')
            ->with($aTestLoaders, array(), $aTestArticle)
            ->will($this->returnValue($aOutput))
        ;
        $this->assertEquals($aOutput, $oPlugin->_loadAdditionalFieldsForArticle(array(), $aTestArticle));
    }

    public function test__loadArticleExport_manufacturer_item_number()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = 'd112_31-2';
        $oTestArticle->oxarticles__oxmpn = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_manufacturer_item_number(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['manufacturer_item_number']);
    }


    public function test__loadArticleExport_currency()
    {
        $sTestCur = "CurName";
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getActiveCurrency'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getActiveCurrency')
            ->will($this->returnValue($sTestCur))
         ;
        $aItem = $oPlugin->_loadArticleExport_currency(array(), $oTestArticle);
        $this->assertEquals($sTestCur, $aItem['currency']);
    }

    public function test__loadArticleExport_tax_percent()
    {
        $sVat = "18.50";
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getArticleVat')
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getArticleVat')
            ->will($this->returnValue($sVat))
        ;
        $aItem = $oPlugin->_loadArticleExport_tax_percent(array(), $oTestArticle);
        $this->assertEquals($sVat, $aItem['tax_percent']);
    }

    public function test__loadArticleExport_msrp()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        /** @var $oPrice oxprice */
        $oPrice = oxNew('oxPrice');
        $oPrice->setPrice(123.456);
        $oTestArticle = $this->getMock(
            'oxArticle',
            array(
                'getTPrice'
            )
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getTPrice')
            ->will($this->returnValue($oPrice))
        ;
        $aItem = $oPlugin->_loadArticleExport_msrp(array(), $oTestArticle);
        $this->assertEquals(123.46, $aItem['msrp']);

    }

    public function test___loadArticleExport_basic_price()
    {
        /** @var $oTestArticle oxArticle */
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        /** @var $oPrice oxprice */
        $oPrice = oxNew('oxPrice');
        $oPrice->setPrice(7.00);
        $oTestArticle->setPrice($oPrice);

        // this should return empty as not defined units
        $aItem = $oPlugin->_loadArticleExport_basic_price(array(), $oTestArticle);
        $this->assertEquals('', $aItem['basic_price']);

        $oTestArticle->oxarticles__oxunitquantity = new oxField(0.7, oxField::T_RAW);
        $oTestArticle->oxarticles__oxunitname = new oxField('liters', oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_basic_price(array(), $oTestArticle);
        $this->assertEquals(10.00, $aItem['basic_price']);
    }

    public function test__loadArticleExport_use_stock()
    {
        $oConfigMock = $this->getMock(
            'oxConfig',
            array(
                'getConfigParam'
            )
        );
        $oConfigMock
            ->expects($this->at(0))
            ->method('getConfigParam')
            ->will($this->returnValue(true))
        ;
        $oConfigMock
            ->expects($this->at(1))
            ->method('getConfigParam')
            ->will($this->returnValue(false))
        ;
        $oConfigMock
            ->expects($this->at(2))
            ->method('getConfigParam')
            ->will($this->returnValue(true))
        ;

        $this->_blResetOxConfig = true;
        modConfig::$unitMOD = $oConfigMock;
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        $oTestArticle = oxNew('oxArticle');

        $oTestArticle->oxarticles__oxstockflag = new oxField(1, oxField::T_RAW);
        $oPlugin->startup();
        $aItem = $oPlugin->_loadArticleExport_use_stock(array(), $oTestArticle);
        $this->assertEquals(1, $aItem['use_stock']);

        $oTestArticle->oxarticles__oxstockflag = new oxField(1, oxField::T_RAW);
        $oPlugin->startup();
        $aItem = $oPlugin->_loadArticleExport_use_stock(array(), $oTestArticle);
        $this->assertEquals(0, $aItem['use_stock']);

        $oTestArticle->oxarticles__oxstockflag = new oxField(4, oxField::T_RAW);
        $oPlugin->startup();
        $aItem = $oPlugin->_loadArticleExport_use_stock(array(), $oTestArticle);
        $this->assertEquals(0, $aItem['use_stock']);
    }

    public function test__loadArticleExport_stock_quantity()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = 12;
        $oTestArticle->oxarticles__oxstock = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_stock_quantity(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['stock_quantity']);
    }

    public function test__loadArticleExport_ean()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = 'ibsn123a1';
        $oTestArticle->oxarticles__oxean = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_ean(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['ean']);
    }

    public function test__loadArticleExport_last_update()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = '2011-09-09 18:00:02';
        $oTestArticle->oxarticles__oxtimestamp = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_last_update(array(), $oTestArticle);
        $this->assertEquals('2011-09-09', $aItem['last_update']);
        $sValue = 'false';
        $oTestArticle->oxarticles__oxtimestamp = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_last_update(array(), $oTestArticle);
        $this->assertEquals('', $aItem['last_update']);
    }

    public function test__loadArticleExport_tags()
    {
        $sTags = "oxid,shop,price";
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestArticle = $this->getMock(
            'oxArticle',
            array('getTags')
        );
        $oTestArticle
            ->expects($this->once())
            ->method('getTags')
            ->will($this->returnValue($sTags))
        ;
        $aItem = $oPlugin->_loadArticleExport_tags(array(), $oTestArticle);
        $this->assertEquals($sTags, $aItem['tags']);
    }

    public function test__loadArticleExport_marketplace()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = 'germany';
        $oTestArticle->oxarticles__marm_shopgate_marketplace = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_marketplace(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['marketplace']);
    }

    public function test__loadArticleExport_weight()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = '2.3';
        $oTestArticle->oxarticles__oxweight = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_weight(array(), $oTestArticle);
        $this->assertEquals(2300, $aItem['weight']);
    }

    public function test__loadArticleExport_is_free_shipping()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sValue = '1';
        $oTestArticle->oxarticles__oxfreeshipping = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_is_free_shipping(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['is_free_shipping']);
        $sValue = '0';
        $oTestArticle->oxarticles__oxfreeshipping = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_is_free_shipping(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['is_free_shipping']);
    }

    public function test__loadArticleExport_block_pricing()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        $aPriceInfo = array();
        $oPriceInfo = new stdClass();
        $oPriceInfo->oxprice2article__oxamount = new oxField(5, oxField::T_RAW);
        $oPriceInfo->fbrutprice = 10;
        $aPriceInfo[] = $oPriceInfo;
        $oPriceInfo = new stdClass();
        $oPriceInfo->oxprice2article__oxamount = new oxField(10, oxField::T_RAW);
        $oPriceInfo->fbrutprice = 8;
        $aPriceInfo[] = $oPriceInfo;
        $oPriceInfo = new stdClass();
        $oPriceInfo->oxprice2article__oxamount = new oxField(15, oxField::T_RAW);
        $oPriceInfo->fbrutprice = 5;
        $aPriceInfo[] = $oPriceInfo;

        $sResult = '5=>10||10=>8||15=>5';

        $oArticleMock = $this->getMock(
            'oxarticle',
            array(
                'loadAmountPriceInfo'
            )
        );
        $oArticleMock
            ->expects($this->once())
            ->method('loadAmountPriceInfo')
            ->will($this->returnValue($aPriceInfo))
        ;
        $aItem = $oPlugin->_loadArticleExport_block_pricing(array(), $oArticleMock);
        $this->assertEquals($sResult, $aItem['block_pricing']);
    }

    public function test__loadArticleExport_properties()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        $aAttributes = array();
        $oAttribute = new stdClass();
        $oAttribute->oxattribute__oxtitle = new oxField('resolution', oxField::T_RAW);
        $oAttribute->oxattribute__oxvalue = new oxField('1024x768', oxField::T_RAW);
        $aAttributes[] = $oAttribute;
        $oAttribute = new stdClass();
        $oAttribute->oxattribute__oxtitle = new oxField('USB', oxField::T_RAW);
        $oAttribute->oxattribute__oxvalue = new oxField('2', oxField::T_RAW);
        $aAttributes[] = $oAttribute;
        $oAttribute = new stdClass();
        $oAttribute->oxattribute__oxtitle = new oxField('SCART', oxField::T_RAW);
        $oAttribute->oxattribute__oxvalue = new oxField('no', oxField::T_RAW);
        $aAttributes[] = $oAttribute;

        $sResult = 'resolution=>1024x768||USB=>2||SCART=>no';

        $oArticleMock = $this->getMock(
            'oxarticle',
            array(
                'getAttributes'
            )
        );
        $oArticleMock
            ->expects($this->once())
            ->method('getAttributes')
            ->will($this->returnValue($aAttributes))
        ;
        $aItem = $oPlugin->_loadArticleExport_properties(array(), $oArticleMock);
        $this->assertEquals($sResult, $aItem['properties']);
    }

    public function test_loadSelectionListForArticle()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aSelList = array();

        $aSelValues = array(
            'oxtitle' => 'color',
            'oxvaldesc' => 'blue__@@red__@@green__@@'
        );
        $oxSelectionList = oxNew('oxSelectlist');
        $oxSelectionList->assign($aSelValues);
        $aSelList[] = $oxSelectionList;

        $aSelValues = array(
            'oxtitle' => 'size',
            'oxvaldesc' => 'S__@@M__@@L__@@'
        );
        $oxSelectionList = oxNew('oxSelectlist');
        $oxSelectionList->assign($aSelValues);
        $aSelList[] = $oxSelectionList;

        $oArticleMock = $this->getMock(
            'oxArticle',
            array(
                'getSelections'
            )
        );
        $oArticleMock
            ->expects($this->at(0))
            ->method('getSelections')
            ->will($this->returnValue(false))
        ;
        $oArticleMock
            ->expects($this->at(1))
            ->method('getSelections')
            ->will($this->returnValue($aSelList))
        ;

        $aItem = $oPlugin->_loadSelectionListForArticle(array(), $oArticleMock);
        $this->assertEquals('0', $aItem['has_options']);

        $aItem = $oPlugin->_loadSelectionListForArticle(array(), $oArticleMock);
        $this->assertEquals('1', $aItem['has_options']);
        $this->assertEquals('color', $aItem['option_1']);
        $this->assertEquals('blue||red||green', $aItem['option_1_values']);
        $this->assertEquals('size', $aItem['option_2']);
        $this->assertEquals('S||M||L', $aItem['option_2_values']);
    }

    public function test__loadVariantsInfoForArticle()
    {
        $aOutput = array('ok');
        $aTestArticle = oxNew('oxArticle');
        $aTestLoaders = array('test7', 'test8', 'test9');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_executeLoaders',
                '_getVariantFieldLoaders'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getVariantFieldLoaders')
            ->will($this->returnValue($aTestLoaders))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_executeLoaders')
            ->with($aTestLoaders, array(), $aTestArticle)
            ->will($this->returnValue($aOutput))
        ;
        $this->assertEquals($aOutput, $oPlugin->_loadVariantsInfoForArticle(array(), $aTestArticle));
    }

    public function test__getVariantFieldLoaders()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aResult = $oPlugin->_getVariantFieldLoaders();
        $this->assertContains('_loadArticleExport_has_children', $aResult);
        $this->assertContains('_loadArticleExport_parent_item_number', $aResult);
        $this->assertContains('_loadArticleExport_attribute', $aResult);
    }

    public function test__loadArticleExport_has_children()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        $sValue = '3';
        $oTestArticle->oxarticles__oxvarcount = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_has_children(array(), $oTestArticle);
        $this->assertEquals(1, $aItem['has_children']);

        $sValue = '0';
        $oTestArticle->oxarticles__oxvarcount = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_has_children(array(), $oTestArticle);
        $this->assertEquals(0, $aItem['has_children']);
    }

    public function test__loadArticleExport_parent_item_number()
    {
        $sValue = 'd112s121';
        $oTestParentArticle = oxNew('oxArticle');
        $oTestParentArticle->oxarticles__oxartnum = new oxField($sValue, oxField::T_RAW);
        $oTestArticle = $this->getMock(
            'oxArticle',
            array(
                'getParentArticle'
            )
        );
        $oTestArticle
            ->expects($this->at(0))
            ->method('getParentArticle')
            ->will($this->returnValue(false))
        ;
        $oTestArticle
            ->expects($this->at(1))
            ->method('getParentArticle')
            ->will($this->returnValue($oTestParentArticle))
        ;
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        $aItem = $oPlugin->_loadArticleExport_parent_item_number(array(), $oTestArticle);
        $this->assertEquals('', $aItem['parent_item_number']);

        $aItem = $oPlugin->_loadArticleExport_parent_item_number(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['parent_item_number']);
    }

    public function test__loadArticleExport_attribute()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        //no variants (variant name or variant selection title)
        $aItem = $oPlugin->_loadArticleExport_attribute(array(), $oTestArticle);
        $this->assertEquals(array(), $aItem);

        // then varname is set , article as child
        $sValue = 'blue';
        $oTestArticle->oxarticles__oxvarname = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_attribute(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['attribute_1']);
        $this->assertArrayNotHasKey('attribute_2', $aItem);

        // then varselect is set , article as parent, overwrites varname if set
        $sValue = 'color';
        $oTestArticle->oxarticles__oxvarselect = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_attribute(array(), $oTestArticle);
        $this->assertEquals($sValue, $aItem['attribute_1']);
        $this->assertArrayNotHasKey('attribute_2', $aItem);

        // multivariant test
        $sValue = 'color | size';
        $oTestArticle->oxarticles__oxvarselect = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadArticleExport_attribute(array(), $oTestArticle);
        $this->assertEquals('color', $aItem['attribute_1']);
        $this->assertEquals('size', $aItem['attribute_2']);
        $this->assertArrayNotHasKey('attribute_3', $aItem);
    }

    public function test__loadPersParamForArticle()
    {
        $oTestArticle = oxNew('oxArticle');
        $oPlugin = $this->getProxyClass('ShopgatePlugin');

        // option not set for article
        $aItem = $oPlugin->_loadPersParamForArticle(array(), $oTestArticle);
        $this->assertEquals(0, $aItem['has_input_fields']);
        $this->assertArrayNotHasKey('input_field_1_type', $aItem);

        // then option is set
        $sValue = '1';
        $oTestArticle->oxarticles__oxisconfigurable = new oxField($sValue, oxField::T_RAW);
        $aItem = $oPlugin->_loadPersParamForArticle(array(), $oTestArticle);
        $this->assertEquals(1, $aItem['has_input_fields']);
        $this->assertEquals('text', $aItem['input_field_1_type']);
        $this->assertArrayHasKey('input_field_1_label', $aItem);
        $this->assertEquals(1, $aItem['input_field_1_required']);
        $this->assertArrayNotHasKey('input_field_2_type', $aItem);
    }

    public function test__getTranslation()
    {
        $sTranslated = 'translated';
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oLangMock = $this->getMock(
            'oxLang',
            array(
                'translateString'
            )
        );
        $oLangMock
            ->expects($this->at(2))
            ->method('translateString')
            ->with('valid')
            ->will($this->returnValue($sTranslated))
        ;
        $oLangMock
            ->expects($this->at(3))
            ->method('translateString')
            ->with('also_valid')
            ->will($this->returnValue($sTranslated))
        ;
        $oLangMock
            ->expects($this->any())
            ->method('translateString')
            ->will($this->returnArgument(0))
        ;
        $this->_blResetInstances = true;
        modInstances::addMod('oxLang', $oLangMock);
        $this->assertEquals($sTranslated, $oPlugin->_getTranslation('invalid', array('also_invalid', 'valid')));
        $this->assertEquals($sTranslated, $oPlugin->_getTranslation('also_valid', array('bad', 'notok')));
        $this->assertEquals('not_valid', $oPlugin->_getTranslation('not_valid', array('not_valid')));
        $this->assertEquals('not_valid', $oPlugin->_getTranslation('not_valid'));
    }

    public function test__getLanguageTagForTable()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $sLangTag  = '_1';
        $sLangAbbr = 'en';
        $oLangMock = $this->getMock(
            'oxLang',
            array(
                'getLanguageTag',
                'getLanguageAbbr'
            )
        );
        $oLangMock
            ->expects($this->exactly(3))
            ->method('getLanguageTag')
            ->will($this->returnValue($sLangTag))
        ;
        $oLangMock
            ->expects($this->exactly(3))
            ->method('getLanguageAbbr')
            ->will($this->returnValue($sLangAbbr))
        ;
        $this->_blResetInstances = true;
        modInstances::addMod('oxLang', $oLangMock);

        $this->assertEquals($sLangTag, $oPlugin->_getLanguageTagForTable('oxarticles'));
        $this->assertEquals($sLangTag, $oPlugin->_getLanguageTagForTable('oxv_oxarticles'));
        $this->assertEquals('', $oPlugin->_getLanguageTagForTable('oxv_oxarticles_en'));
    }

    public function test_createCategoriesCsv()
    {
        //empty function currently
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertNull($oPlugin->createCategoriesCsv());
    }

    public function test_createReviewsCsv()
    {
        //empty function currently
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertNull($oPlugin->createReviewsCsv());
    }

    public function test_createPagesCsv()
    {
        //empty function currently
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertNull($oPlugin->createPagesCsv());
    }

    public function test_createShopInfo()
    {
        //empty function currently
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertNull($oPlugin->createShopInfo());
    }

    public function test__createShopCustomerObject()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertType('ShopgateShopCustomer', $oPlugin->_createShopCustomerObject());
    }

    public function test_getUserData()
    {
        $aTestUser = array(
            'CustomerNumber' => '123',
            'FirstName' => 'Name',
            'Surname' => 'Lastname',
            'Mail' => 'no@mail.com',
            'Phone' => '1221212',
            'Mobile' => '69219422132',
            'Gender' => ShopgateShopCustomer::MALE,
            'Street' => 'strase 15',
            'Street1' => 'strase',
            'Street2' => '15',
            'City' => 'marma',
            'Zip' => '43212',
            'Country' => 'Germany',
            'Company' => 'marmala',

        );
        $oShopgateCustomer = $this->getMock(
            'ShopgateShopCustomer',
            array(
                'setCustomerNumber',
                'setFirstName',
                'setSurname',
                'setMail',
                'setPhone',
                'setMobile',
                'setGender',
                'setStreet',
                'setCity',
                'setZip',
                'setCountry',
                'setCompany'
            )
        );
        $oShopgateCustomer->expects($this->exactly(2))->method('setCustomerNumber')->with($aTestUser['CustomerNumber']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setFirstName')->with($aTestUser['FirstName']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setSurname')->with($aTestUser['Surname']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setMail')->with($aTestUser['Mail']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setPhone')->with($aTestUser['Phone']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setMobile')->with($aTestUser['Mobile']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setStreet')->with($aTestUser['Street']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setCity')->with($aTestUser['City']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setZip')->with($aTestUser['Zip']);
        $oShopgateCustomer->expects($this->exactly(2))->method('setCountry')->with($aTestUser['Country']);

        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'), 
            array(
                '_createShopCustomerObject'
            )
        );
        $oPlugin
            ->expects($this->atLeastOnce())
            ->method('_createShopCustomerObject')
            ->will($this->returnValue($oShopgateCustomer))
        ;
        $oUserMock = $this->getMock(
            'oxUser',
            array(
                'login',
                'getUserCountry'
            )
        );
        $oUserMock
            ->expects($this->atLeastOnce())
            ->method('getUserCountry')
            ->will($this->returnValue($aTestUser['Country']))
        ;
        $sBadUser = '';
        $sBadPassword = '';
        $oBadException = oxNew( 'oxUserException' );
        $oBadException->setMessage( 'EXCEPTION_USER_NOVALIDLOGIN' );

        $oUserMock
            ->expects($this->at(0))
            ->method('login')
            ->will($this->throwException($oBadException))
        ;
        $this->_blResetModules = true;
        oxTestModules::addModuleObject('oxUser', $oUserMock);
        
        try {
            $oPlugin->getUserData($sBadUser, $sBadPassword);
            $this->fail('Bad login should throw exception');
        }
        catch(ShopgateConnectException $oEx) {
            $this->assertEquals(ShopgateConnectException::INVALID_USERNAME_OR_PASSWORD, $oEx->getCode());
        }
        $oUserMock
            ->expects($this->any())
            ->method('login')
        ;

        $oUserMock->oxuser__oxcustnr    = new oxField($aTestUser['CustomerNumber'], oxField::T_RAW);
        $oUserMock->oxuser__oxfname     = new oxField($aTestUser['FirstName'], oxField::T_RAW);
        $oUserMock->oxuser__oxlname     = new oxField($aTestUser['Surname'], oxField::T_RAW);
        $oUserMock->oxuser__oxusername  = new oxField($aTestUser['Mail'], oxField::T_RAW);
        $oUserMock->oxuser__oxfon       = new oxField($aTestUser['Phone'], oxField::T_RAW);
        $oUserMock->oxuser__oxmobfon    = new oxField($aTestUser['Mobile'], oxField::T_RAW);
        $oUserMock->oxuser__oxsal       = new oxField('MR', oxField::T_RAW);
        $oUserMock->oxuser__oxstreet    = new oxField($aTestUser['Street1'], oxField::T_RAW);
        $oUserMock->oxuser__oxstreetnr  = new oxField($aTestUser['Street2'], oxField::T_RAW);
        $oUserMock->oxuser__oxcity      = new oxField($aTestUser['City'], oxField::T_RAW);
        $oUserMock->oxuser__oxzip       = new oxField($aTestUser['Zip'], oxField::T_RAW);
        $oUserMock->oxuser__oxcompany   = new oxField($aTestUser['Company'], oxField::T_RAW);

        $this->assertType('ShopgateShopCustomer', $oPlugin->getUserData('', ''));

        $oUserMock->oxuser__oxcompany   = new oxField('', oxField::T_RAW);
        $oUserMock->oxuser__oxsal       = new oxField('MRS', oxField::T_RAW);
        $this->assertType('ShopgateShopCustomer', $oPlugin->getUserData('', ''));

    }

    public function test__getOrderImportLoaders()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aResult = $oPlugin->_getOrderImportLoaders();
        $this->assertContains('_loadOrderPrice', $aResult);
        $this->assertContains('_loadOrderRemark', $aResult);
        $this->assertContains('_loadOrderContacts', $aResult);
        $this->assertContains('_loadOrderAdditionalInfo', $aResult);
    }

    public function test_saveOrder()
    {
        $oTestOrder = $this->getMock(
            'oxOrder',
            array(
                'save'
            )
        );
        $oTestOrder
            ->expects($this->once())
            ->method('save')
        ;
        $this->_blResetModules = true;
        oxTestModules::addModuleObject('oxOrder', $oTestOrder);
        $oShopgateOrder = new ShopgateOrder();
        $aTestLoaders = array('test123');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_checkOrder',
                '_executeLoaders',
                '_getOrderImportLoaders',
                '_saveOrderArticles'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_checkOrder')
            ->with($oShopgateOrder)
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_getOrderImportLoaders')
            ->will($this->returnValue($aTestLoaders))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_executeLoaders')
            ->with($aTestLoaders, $oTestOrder, $oShopgateOrder)
            ->will($this->returnValue($oTestOrder))
        ;
        $oPlugin
            ->expects($this->once())
            ->method('_saveOrderArticles')
            ->with( $oTestOrder, $oShopgateOrder)
        ;
        $this->assertNull($oPlugin->saveOrder($oShopgateOrder));

    }

    public function test__loadOrderPrice()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oTestOrder = oxNew('oxOrder');
        $dTotalSum = 123.12;
        $dArticleSum = 103.12;
        $sCurrency = 'EUR';
        $dDefaultVat = 17;
        $dArticleSumNetto = round($dArticleSum / (1+($dDefaultVat/100)), 2);
        $dArticleVatSum = $dArticleSum - $dArticleSumNetto;
        $oShopgateOrder = $this->getMock(
            'ShopgateOrder',
            array(
                'getAmountComplete',
                'getAmountItems',
                'getOrderCurrency'
            )
        );
        $oShopgateOrder
            ->expects($this->once())
            ->method('getAmountComplete')
            ->will($this->returnValue($dTotalSum*100))
        ;
        $oShopgateOrder
            ->expects($this->once())
            ->method('getAmountItems')
            ->will($this->returnValue($dArticleSum*100))
        ;
        $oShopgateOrder
            ->expects($this->once())
            ->method('getOrderCurrency')
            ->will($this->returnValue($sCurrency))
        ;
        modConfig::getInstance()->setConfigParam('dDefaultVAT', $dDefaultVat);

        $oResult = $oPlugin->_loadOrderPrice($oTestOrder, $oShopgateOrder);

        $this->assertEquals($dTotalSum ,$oResult->oxorder__oxtotalordersum->value);
        $this->assertEquals($dArticleSumNetto ,$oResult->oxorder__oxtotalnetsum->value);
        $this->assertEquals($dArticleSum ,$oResult->oxorder__oxtotalbrutsum->value);
        $this->assertEquals($dDefaultVat ,$oResult->oxorder__oxartvat1->value);
        $this->assertEquals($dArticleVatSum ,$oResult->oxorder__oxartvatprice1->value);
        $this->assertEquals($sCurrency ,$oResult->oxorder__oxcurrency->value);
        $this->assertEquals(1, $oResult->oxorder__oxcurrate->value);
    }

    public function test__loadOrderRemark()
    {
        $sSingleString = 'orderremarkhere';
        $aArray = array('order','remark','here');
        $sConvertedArray = "array (\n  0 => 'order',\n  1 => 'remark',\n  2 => 'here',\n)";
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oShopgateOrderMock = $this->getMock(
            'ShopgateOrder',
            array(
                'getDeliversNotes'
            )
        );
        $oShopgateOrderMock
            ->expects($this->at(0))
            ->method('getDeliversNotes')
            ->will($this->returnValue(null))
        ;
        $oShopgateOrderMock
            ->expects($this->at(1))
            ->method('getDeliversNotes')
            ->will($this->returnValue($sSingleString))
        ;
        $oShopgateOrderMock
            ->expects($this->at(2))
            ->method('getDeliversNotes')
            ->will($this->returnValue($aArray))
        ;

        $oTestOrder = oxNew('oxOrder');
        $oResult = $oPlugin->_loadOrderRemark($oTestOrder, $oShopgateOrderMock);
        $this->assertEquals('', $oResult->oxorder__oxremark->value);

        $oTestOrder = oxNew('oxOrder');
        $oResult = $oPlugin->_loadOrderRemark($oTestOrder, $oShopgateOrderMock);
        $this->assertEquals($sSingleString, $oResult->oxorder__oxremark->value);

        $oTestOrder = oxNew('oxOrder');
        $oResult = $oPlugin->_loadOrderRemark($oTestOrder, $oShopgateOrderMock);
        $this->assertEquals($sConvertedArray, $oResult->oxorder__oxremark->value);
    }

    public function test__loadOrderAdditionalInfo()
    {
        $iShopgateOrderId = 1231231234;
        $sOrderLang = 1;
        $aOrderFolders = array(
            'First' => 'one',
            'Second' => 'one'
        );
        modConfig::getInstance()->setConfigParam('aOrderfolder', $aOrderFolders);
        $oShopgateOrder = $this->getMock(
            'ShopgateOrder',
            array(
                'getOrderNumber'
            )
        );
        $oShopgateOrder
            ->expects($this->once())
            ->method('getOrderNumber')
            ->will($this->returnValue($iShopgateOrderId))
        ;

        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $oOrderMock = $this->getMock(
            'oxOrder',
            array(
                'getOrderLanguage'
            )
        );
        $oOrderMock
            ->expects($this->once())
            ->method('getOrderLanguage')
            ->will($this->returnValue($sOrderLang))
        ;
        $oResult = $oPlugin->_loadOrderAdditionalInfo($oOrderMock, $oShopgateOrder);
        $this->assertEquals($sOrderLang, $oResult->oxorder__oxlang->value);
        $this->assertEquals('FROM_SHOPGATE', $oResult->oxorder__oxtransstatus->value);
        $this->assertEquals('First', $oResult->oxorder__oxfolder->value);
        $this->assertEquals($iShopgateOrderId, $oResult->oxorder__marm_shopgate_order_number->value);
    }

    public function test__loadOrderContacts()
    {
        $oTestOrder = oxNew('oxOrder');
        $sOrderEmail = 'no@email.here';
        $sUserId = 'oxiduserid';
        $oInvoiceAddress = $this->getMock('ShopgateOrderAddress');
        $oShippingAddress = $this->getMock('ShopgateOrderAddress');
        $oShopgateOrderMock = $this->getMock(
            'ShopgateOrder',
            array(
                'getCustomerMail',
                'getInvoiceAddress',
                'getDeliveryAddress'
            )
        );
        $oShopgateOrderMock
            ->expects($this->once())
            ->method('getCustomerMail')
            ->will($this->returnValue($sOrderEmail))
        ;
        $oShopgateOrderMock
            ->expects($this->once())
            ->method('getInvoiceAddress')
            ->will($this->returnValue($oInvoiceAddress))
        ;
        $oShopgateOrderMock
            ->expects($this->once())
            ->method('getDeliveryAddress')
            ->will($this->returnValue($oShippingAddress))
        ;
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getUserOxidByEmail',
                '_loadOrderAddress'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_getUserOxidByEmail')
            ->with($sOrderEmail)
            ->will($this->returnValue($sUserId))
        ;
        $oPlugin
            ->expects($this->at(1))
            ->method('_loadOrderAddress')
            ->with($oTestOrder, $oShopgateOrderMock, 'oxbill', $oInvoiceAddress)
            ->will($this->returnValue($oTestOrder))
        ;
        $oPlugin
            ->expects($this->at(2))
            ->method('_loadOrderAddress')
            ->with($oTestOrder, $oShopgateOrderMock, 'oxdel', $oShippingAddress)
            ->will($this->returnValue($oTestOrder))
        ;

        $this->assertEquals($oTestOrder, $oPlugin->_loadOrderContacts($oTestOrder, $oShopgateOrderMock));
    }

    public function test__loadOrderAddress()
    {
        $oContact = new stdClass();
        $oContact->getCompany= 'corporation_name';
        $oContact->getFirstName = 'main_name';
        $oContact->getSurname = 'sur_name';
        $oContact->getStreet = 'strase';
        $oContact->getCity = 'hamburg_city';
        $oContact->getCountry = 'DE';
        $oContact->getCountryName = 'germany';
        $oContact->country_oxid = 'oxid_country';
        $oContact->getState = 'HH';
        $oContact->getStateName = 'Hamburg';
        $oContact->state_oxid = 'oxid_state';
        $oContact->getZipcode = '121223';
        $oContact->getCustomerPhone = '+4722322212';
        $oContact->getCustomerFax = '+477777777';
        $oContact->getCustomerMobile = new StringChanger('+472346789');
        $oContact->getGender = new StringChanger('m');

        $oTestOrder = oxNew('oxOrder');
        $oShopgateOrderMock = $this->getMock(
            'ShopgateOrder',
            array(
                'getCustomerMobile',
                'getCustomerPhone',
                'getCustomerFax'
            )
        );
        $oShopgateOrderMock
            ->expects($this->atLeastOnce())
            ->method('getCustomerMobile')
            ->will($this->returnValue($oContact->getCustomerMobile))
        ;
        $oShopgateOrderMock
            ->expects($this->atLeastOnce())
            ->method('getCustomerPhone')
            ->will($this->returnValue($oContact->getCustomerPhone))
        ;
        $oShopgateOrderMock
            ->expects($this->atLeastOnce())
            ->method('getCustomerFax')
            ->will($this->returnValue($oContact->getCustomerFax))
        ;
        $oShopgateOrderAddressMock = $this->getMock(
            'ShopgateOrderAddress',
            array(
                'getCompany',
                'getFirstName',
                'getSurname',
                'getStreet',
                'getCity',
                'getCountry',
                'getCountryName',
                'getState',
                'getStateName',
                'getZipcode',
                'getGender'
            )
        );
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getCompany')
                ->will($this->returnValue($oContact->getCompany))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getFirstName')
                ->will($this->returnValue($oContact->getFirstName))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getSurname')
                ->will($this->returnValue($oContact->getSurname))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getStreet')
                ->will($this->returnValue($oContact->getStreet))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getCity')
                ->will($this->returnValue($oContact->getCity))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getCountry')
                ->will($this->returnValue($oContact->getCountry))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getCountryName')
                ->will($this->returnValue($oContact->getCountryName))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getState')
                ->will($this->returnValue($oContact->getState))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getStateName')
                ->will($this->returnValue($oContact->getStateName))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getZipcode')
                ->will($this->returnValue($oContact->getZipcode))
        ;
        $oShopgateOrderAddressMock
                ->expects($this->atLeastOnce())
                ->method('getGender')
                ->will($this->returnValue($oContact->getGender))
        ;
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getCountryId',
                '_getStateId'
            )
        );
        $oPlugin
            ->expects($this->atLeastOnce())
            ->method('_getCountryId')
            ->with($oContact->getCountry, $oContact->getCountryName)
            ->will($this->returnValue($oContact->country_oxid))
         ;
        $oPlugin
            ->expects($this->atLeastOnce())
            ->method('_getStateId')
            ->with($oContact->getState, $oContact->getStateName)
            ->will($this->returnValue($oContact->state_oxid))
         ;

        $oResult = $oPlugin->_loadOrderAddress($oTestOrder, $oShopgateOrderMock, 'oxbill', $oShopgateOrderAddressMock);
        $this->assertEquals($oTestOrder, $oResult);
        $this->assertEquals($oContact->getCompany, $oResult->oxorder__oxbillcompany->value);
        $this->assertEquals($oContact->getFirstName, $oResult->oxorder__oxbillfname->value);
        $this->assertEquals($oContact->getSurname, $oResult->oxorder__oxbilllname->value);
        $this->assertEquals($oContact->getStreet, $oResult->oxorder__oxbillstreet->value);
        $this->assertEquals($oContact->getCity, $oResult->oxorder__oxbillcity->value);
        $this->assertEquals($oContact->country_oxid, $oResult->oxorder__oxbillcountryid->value);
        $this->assertEquals($oContact->state_oxid, $oResult->oxorder__oxbillstateid->value);
        $this->assertEquals($oContact->getZipcode, $oResult->oxorder__oxbillzip->value);
        $this->assertEquals($oContact->getCustomerMobile->__toString(), $oResult->oxorder__oxbillfon->value);
        $this->assertEquals($oContact->getCustomerFax, $oResult->oxorder__oxbillfax->value);
        $this->assertEquals('MR', $oResult->oxorder__oxbillsal->value);

        $oContact->getCustomerMobile->setString('');
        $oContact->getGender->setString('f');
        $oResult = $oPlugin->_loadOrderAddress($oTestOrder, $oShopgateOrderMock, 'oxdel', $oShopgateOrderAddressMock);
        $this->assertEquals($oContact->getCustomerPhone, $oResult->oxorder__oxdelfon->value);
        $this->assertEquals('MRS', $oResult->oxorder__oxdelsal->value);

    }

    public function test__getUserOxidByEmail()
    {
        $sUserEmail = 'some@ema.il';
        $sExpectedOutput = 'asdfwqer123';
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_dbGetOne'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_dbGetOne')
            ->with($this->stringContains('SELECT OXID FROM oxuser WHERE OXUSERNAME'), array($sUserEmail))
            ->will($this->returnValue($sExpectedOutput))
        ;
        $this->assertEquals($sExpectedOutput, $oPlugin->_getUserOxidByEmail($sUserEmail));
    }

    public function test__getCountryId()
    {
        $sCode = 'DE';
        $sName = 'germany';
        $sExpectedOutput = 'fdsaw21';
        $sLangTag  = '_1';
        $oLangMock = $this->getMock(
            'oxLang',
            array(
                'getLanguageTag'
            )
        );
        $oLangMock
            ->expects($this->once())
            ->method('getLanguageTag')
            ->will($this->returnValue($sLangTag))
        ;
        $this->_blResetInstances = true;
        modInstances::addMod('oxLang', $oLangMock);

        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_dbGetOne'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_dbGetOne')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('oxcountry WHERE OXISOALPHA2 ='),
                    $this->stringContains("OXTITLE{$sLangTag}")
                ),
                array($sCode, $sName)
            )
            ->will($this->returnValue($sExpectedOutput))
        ;
        $this->assertEquals($sExpectedOutput, $oPlugin->_getCountryId($sCode, $sName));

    }

    public function test__getStateId()
    {
        $sCode = 'BE';
        $sName = 'Berlin';
        $sExpectedOutput = 'huyasd2243';
        $sLangTag  = '_1';
        $oLangMock = $this->getMock(
            'oxLang',
            array(
                'getLanguageTag'
            )
        );
        $oLangMock
            ->expects($this->once())
            ->method('getLanguageTag')
            ->will($this->returnValue($sLangTag))
        ;
        $this->_blResetInstances = true;
        modInstances::addMod('oxLang', $oLangMock);

        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_dbGetOne'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_dbGetOne')
            ->with(
                $this->logicalAnd(
                    $this->stringContains('oxstates WHERE OXISOALPHA2 ='),
                    $this->stringContains("OXTITLE{$sLangTag}")
                ),
                array($sCode, $sName)
            )
            ->will($this->returnValue($sExpectedOutput))
        ;
        $this->assertEquals($sExpectedOutput, $oPlugin->_getStateId($sCode, $sName));

    }

    public function test__getArticleByNumber()
    {
        $sArtNum = 'n311-w';
        $sExpectedOutput = 'cdss2as';
        $sArticleTable = getViewName('oxarticles');
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_dbGetOne'
            )
        );
        $oPlugin
            ->expects($this->once())
            ->method('_dbGetOne')
            ->with($this->stringContains("SELECT oxid FROM {$sArticleTable} WHERE oxartnum ="), array($sArtNum))
            ->will($this->returnValue($sExpectedOutput))
        ;
        $oArticleMock = $this->getMock(
            'oxArticle',
            array(
                'load'
            )
        );
        $oArticleMock
            ->expects($this->once())
            ->method('load')
            ->with($sExpectedOutput)
        ;
        $this->_blResetModules = true;
        oxTestModules::addModuleObject('oxArticle', $oArticleMock);
        
        $this->assertEquals($oArticleMock, $oPlugin->_getArticleByNumber($sArtNum));
    }

    public function test__saveOrderArticles()
    {
        $this->_blResetModules = true;
        $sOxidOrderId = 'testorderid1';
        $sOxidOrderArticleId = 'sOxidOrderArticleId2';
        $sOxidArticleId = 'sOxidArticleId3';
        $sOxidShopId = 'sOxidArticleId4';
        $oOrderItem = new stdClass();
        $oOrderItem->getItemNumber = 'art_num';
        $oOrderItem->getName = 'title_here';
        $oOrderItem->getQuantity = '2';
        $oOrderItem->getUnitAmount = '200';
        $oOrderItem->getUnitAmountWithTax = '232';
        $oOrderItem->getTaxPercent = '16';

        $oProductMock = $this->getMock(
            'oxArticle',
            array(
                'getId',
                'getShopId'
            )
        );
        $oProductMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue($sOxidArticleId))
        ;
        $oProductMock
            ->expects($this->atLeastOnce())
            ->method('getShopId')
            ->will($this->returnValue($sOxidShopId))
        ;

        $oShopgateOrderItemMock = $this->getMock(
            'ShopgateOrderItem',
            array(
                'getItemNumber',
                'getName',
                'getQuantity',
                'getUnitAmount',
                'getUnitAmountWithTax',
                'getTaxPercent'
            )
        );
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getItemNumber')->will($this->returnValue($oOrderItem->getItemNumber));
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getName')->will($this->returnValue($oOrderItem->getName));
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getQuantity')->will($this->returnValue($oOrderItem->getQuantity));
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getUnitAmount')->will($this->returnValue($oOrderItem->getUnitAmount));
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getUnitAmountWithTax')->will($this->returnValue($oOrderItem->getUnitAmountWithTax));
        $oShopgateOrderItemMock->expects($this->atLeastOnce())
                ->method('getTaxPercent')->will($this->returnValue($oOrderItem->getTaxPercent));

        $oShopgateOrderMock = $this->getMock(
            'ShopgateOrder',
            array(
                'getOrderItems'
            )
        );
        $oShopgateOrderMock
                ->expects($this->once())
                ->method('getOrderItems')
                ->will($this->returnValue(array($oShopgateOrderItemMock)))
        ;
        $oOrderArticleMock = $this->getMock(
            'oxorderarticle',
            array(
                'setIsNewOrderItem',
                'copyThis',
                'setId',
                'getId'
            )
        );
        $oOrderArticleMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue($sOxidOrderArticleId))
        ;
        $oOrderArticleMock
            ->expects($this->atLeastOnce())
            ->method('setIsNewOrderItem')
            ->with(true)
        ;
        $oOrderArticleMock
            ->expects($this->atLeastOnce())
            ->method('setId')
        ;
        $oOrderArticleMock
            ->expects($this->atLeastOnce())
            ->method('copyThis')
            ->with($oProductMock)
        ;
        oxTestModules::addModuleObject('oxorderarticle', $oOrderArticleMock);

        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_getArticleByNumber',
                '_loadSelectionsForOrderArticle'
            )
        );
        $oPlugin
            ->expects($this->atLeastOnce())
            ->method('_getArticleByNumber')
            ->with($oOrderItem->getItemNumber)
            ->will($this->returnValue($oProductMock))
        ;
        $oPlugin
            ->expects($this->atLeastOnce())
            ->method('_loadSelectionsForOrderArticle')
//            ->with($oOrderArticleMock, $oShopgateOrderItemMock, $oProductMock)
            ->will($this->returnValue($oOrderArticleMock))
        ;

        $oListMock = $this->getMock(
            'oxList',
            array(
                'offsetSet'
            )
        );
        $oListMock
            ->expects($this->atLeastOnce())
            ->method('offsetSet')
            ->with($sOxidOrderArticleId, $oOrderArticleMock)
        ;
        oxTestModules::addModuleObject('oxList', $oListMock);

        $oOrderMock = $this->getMock(
            'oxOrder',
            array(
                'setOrderArticleList',
                'save',
                'getId'
            )
        );
        $oOrderMock
            ->expects($this->once())
            ->method('save')
        ;
        $oOrderMock
            ->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue($sOxidOrderId))
        ;
        $oOrderMock
            ->expects($this->once())
            ->method('setOrderArticleList')
            ->with($oListMock)
        ;
        $oPlugin->_saveOrderArticles($oOrderMock, $oShopgateOrderMock);
        $oOrderArticle = $oOrderArticleMock;

        $this->assertEquals($oOrderItem->getItemNumber, $oOrderArticle->oxorderarticles__oxartnum->value);
        $this->assertEquals($oOrderItem->getName, $oOrderArticle->oxorderarticles__oxtitle->value);
        $this->assertEquals($sOxidOrderId, $oOrderArticle->oxorderarticles__oxorderid->value);
        $this->assertEquals($sOxidArticleId, $oOrderArticle->oxorderarticles__oxartid->value);
        $this->assertEquals($oOrderItem->getQuantity, $oOrderArticle->oxorderarticles__oxamount->value);
        $this->assertEquals(2.00, $oOrderArticle->oxorderarticles__oxnetprice->value);
        $this->assertEquals(0.32, $oOrderArticle->oxorderarticles__oxvatprice->value);
        $this->assertEquals(2.32, $oOrderArticle->oxorderarticles__oxbrutprice->value);
        $this->assertEquals(16, $oOrderArticle->oxorderarticles__oxvat->value);
        $this->assertEquals(1.00, $oOrderArticle->oxorderarticles__oxnprice->value);
        $this->assertEquals(1.16, $oOrderArticle->oxorderarticles__oxbprice->value);
        $this->assertEquals($sOxidShopId, $oOrderArticle->oxorderarticles__oxordershopid->value);

    }

    public function test__loadSelectionsForOrderArticle()
    {
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $aOptions = array(
            array(
                'optionname1',
                'optoinval2'
            ),
            array(
                'optionname3',
                'optoinval4'
            )
        );
        $oShopgateOrderItemOptionMock = $this->getMock(
            'ShopgateOrderItemOption',
            array(
                'getName',
                'getValue'
            )
        );
        $oShopgateOrderItemOptionMock
            ->expects($this->at(0))
            ->method('getName')
            ->will($this->returnValue($aOptions[0][0]))
        ;
        $oShopgateOrderItemOptionMock
            ->expects($this->at(1))
            ->method('getValue')
            ->will($this->returnValue($aOptions[0][1]))
        ;
        $oShopgateOrderItemOptionMock
            ->expects($this->at(2))
            ->method('getName')
            ->will($this->returnValue($aOptions[1][0]))
        ;
        $oShopgateOrderItemOptionMock
            ->expects($this->at(3))
            ->method('getValue')
            ->will($this->returnValue($aOptions[1][1]))
        ;

        $oShopgateOrderItemMock = $this->getMock(
            'ShopgateOrderItem',
            array(
                'getHasOptions',
                'getOptions'
            )
        );
        $oShopgateOrderItemMock
            ->expects($this->at(0))
            ->method('getHasOptions')
            ->will($this->returnValue(false))
        ;
        $oShopgateOrderItemMock
            ->expects($this->any())
            ->method('getHasOptions')
            ->will($this->returnValue(true))
        ;
        $oShopgateOrderItemMock
            ->expects($this->atLeastOnce())
            ->method('getOptions')
            ->will($this->returnValue(array($oShopgateOrderItemOptionMock, $oShopgateOrderItemOptionMock)))
        ;
        $oTestProduct = oxNew('oxArticle');
        $oTestProduct->oxarticles__oxvarselect = new oxField('color');
        $oTestOrder = oxNew('oxOrderArticle');

        $oResult = $oPlugin->_loadSelectionsForOrderArticle($oTestOrder, $oShopgateOrderItemMock, $oTestProduct);
        $this->assertEquals('color', $oResult->oxorderarticles__oxselvariant->value);

        $oResult = $oPlugin->_loadSelectionsForOrderArticle($oTestOrder, $oShopgateOrderItemMock, $oTestProduct);
        $this->assertEquals('optionname1 : optoinval2, optionname3 : optoinval4 color', $oResult->oxorderarticles__oxselvariant->value);

    }

    public function test__getActiveCurrency()
    {
        $oConfigMock = $this->getMock(
            'oxConfig',
            array(
                'getActShopCurrencyObject',
            )
        );
        $oCur1 = new stdClass();
        $oCur1->name = 'EUR';
        $oCur2 = new stdClass();
        $oCur2->name = 'USD';

        $oConfigMock
            ->expects($this->at(0))
            ->method('getActShopCurrencyObject')
            ->will($this->returnValue($oCur1))
        ;
        $oConfigMock
            ->expects($this->at(1))
            ->method('getActShopCurrencyObject')
            ->will($this->returnValue($oCur2))
        ;
        $this->_blResetOxConfig = true;
        modConfig::$unitMOD = $oConfigMock;
        $oPlugin = $this->getProxyClass('ShopgatePlugin');
        $this->assertEquals($oCur1->name, $oPlugin->_getActiveCurrency());
        $this->assertEquals($oCur1->name, $oPlugin->_getActiveCurrency());
        $this->assertEquals($oCur2->name, $oPlugin->_getActiveCurrency(true));
    }

    public function test__checkOrder()
    {
        $sShopgateOrderId = 1231231234;
        $sStoredOrderId = '321';
        $oShopgateOrderMock = $this->getMock(
            'ShopgateOrder',
            array(
                'getOrderNumber'
            )
        );
        $oShopgateOrderMock
            ->expects($this->exactly(2))
            ->method('getOrderNumber')
            ->will($this->returnValue($sShopgateOrderId))
        ;
        $oPlugin = $this->getMock(
            $this->getProxyClassName('ShopgatePlugin'),
            array(
                '_dbGetOne'
            )
        );
        $oPlugin
            ->expects($this->at(0))
            ->method('_dbGetOne')
            ->with($this->stringContains('oxorder WHERE marm_shopgate_order_number ='), array($sShopgateOrderId))
            ->will($this->returnValue($sStoredOrderId))
        ;
        $oPlugin
            ->expects($this->at(1))
            ->method('_dbGetOne')
            ->with($this->stringContains('oxorder WHERE marm_shopgate_order_number ='), array($sShopgateOrderId))
            ->will($this->returnValue(null))
        ;
        try {
            $oPlugin->_checkOrder($oShopgateOrderMock);
            $this->fail('Exception expected, then order exists');
        }
        catch(ShopgateFrameworkException $oEx) {
            $this->assertContains($sStoredOrderId, $oEx->getMessage());
        }

        // no such order test
        $oPlugin->_checkOrder($oShopgateOrderMock);

    }
}