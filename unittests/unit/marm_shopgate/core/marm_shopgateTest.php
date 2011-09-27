<?php

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';

class unit_marm_shopgate_core_marm_shopgateTest extends OxidTestCase
{
    protected $_oOldMarmShopgateInstance = null;

    public function tearDown()
    {
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
        unset($_GLOBAL['marm_shopgate_include_counter']);
    }

    public function test__getFrameworkDir()
    {
        $oMarmShopgate = $this->getProxyClass('marm_shopgate');
        $sResult = $oMarmShopgate->_getFrameworkDir();
        $this->assertStringStartsWith(getShopBasePath(), $sResult);
        $this->assertStringEndsWith('/shopgate/', $sResult);
        $this->assertStringEndsWith('/'.marm_shopgate::FRAMEWORK_DIR.'/', $sResult);
        $this->assertTrue(file_exists($sResult) && is_dir($sResult));
    }

    public function test__getLibraryDir()
    {
        $sFrameworkDir = '/some/path/';
        $oMarmShopgate = $this->getMock(
            $this->getProxyClassName('marm_shopgate'),
            array(
                '_getFrameworkDir'
            )
        );

        $oMarmShopgate
            ->expects($this->once())
            ->method('_getFrameworkDir')
            ->will($this->returnValue($sFrameworkDir))
        ;

        $sResult = $oMarmShopgate->_getLibraryDir();
        $this->assertStringStartsWith($sFrameworkDir, $sResult);
        $this->stringEndsWith('lib/', $sResult);
    }

    public function test__getFilesToInclude()
    {
        $oMarmShopgate = $this->getProxyClass('marm_shopgate');
        $aResult = $oMarmShopgate->_getFilesToInclude();
        $this->assertContains('framework.php', $aResult);
        $this->assertContains('connect_api.php', $aResult);
        $this->assertContains('core_api.php', $aResult);
        $this->assertContains('order_api.php', $aResult);
    }

    public function test_init()
    {
        $aTestFilesToInclude = array(
            'testinclude1.php',
            'testinclude2.php',
            'testinclude1.php'
        );
        $sIncludeDir = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $sFilePath1 = $sIncludeDir.$aTestFilesToInclude[0];
        $sFilePath2 = $sIncludeDir.$aTestFilesToInclude[1];
        file_put_contents($sFilePath1, '<?php $GLOBALS[\'marm_shopgate_include_counter\'] += 1;');
        file_put_contents($sFilePath2, '<?php ;$GLOBALS[\'marm_shopgate_include_counter\'] += 2;');
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                '_getFilesToInclude',
                '_getLibraryDir',
                '_getConfigForFramework'
            )
        );
        $oMarmShopgate
            ->expects($this->exactly(2))
            ->method('_getFilesToInclude')
            ->will($this->returnValue($aTestFilesToInclude))
        ;
        $oMarmShopgate
            ->expects($this->exactly(2))
            ->method('_getLibraryDir')
            ->will($this->returnValue($sIncludeDir))
        ;
        $oMarmShopgate
            ->expects($this->exactly(2))
            ->method('_getConfigForFramework')
        ;
        $GLOBALS['marm_shopgate_include_counter'] = 0;

        $oMarmShopgate->init();

        $this->assertEquals(3, $GLOBALS['marm_shopgate_include_counter']);

        // test for double call (require_once should include only one time, so counter will be not touched)
        $GLOBALS['marm_shopgate_include_counter'] = 9;
        $oMarmShopgate->init();
        $this->assertEquals(9, $GLOBALS['marm_shopgate_include_counter']);
        
    }

    public function test_getFramework()
    {
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                 'init'
            )
        );

        $oMarmShopgate
            ->expects($this->exactly(2))
            ->method('init')
        ;
        $this->assertTrue($oMarmShopgate->getFramework() instanceof shopgateFramework);
        $this->assertTrue($oMarmShopgate->getFramework() instanceof shopgateFramework);
        $this->assertTrue($oMarmShopgate->getFramework(true) instanceof shopgateFramework);
    }

    public function test__getConfig()
    {
        $aRequiredKeys = array(
            'customer_number',
            'shop_number',
            'apikey',
            'plugin'
        );
        $oMarmShopgate = $this->getProxyClass('marm_shopgate');
        $aResult = $oMarmShopgate->_getConfig();
        foreach ($aRequiredKeys as $sKey) {
            $this->assertArrayHasKey($sKey, $aResult);
        }
    }

    public function test__getConfigForFramework()
    {
        $aRequiredKeys = array(
            'customer_number' => false,
            'shop_number' => false,
            'apikey' => false,
            'plugin' => false
        );
        $aOxidConfig = array(
            'customer_number' => 123321,
            'shop_number' => 54321,
            'apikey' => 'asdffdassfd'
        );
        $oMarmShopgate = $this->getMock(
            $this->getProxyClassName('marm_shopgate'),
            array(
                '_getConfig',
                'getOxidConfigKey'
            )
        );
        $oMarmShopgate
            ->expects($this->once())
            ->method('_getConfig')
            ->will($this->returnValue($aRequiredKeys))
        ;
        $oMarmShopgate
            ->expects($this->atLeastOnce())
            ->method('getOxidConfigKey')
            ->will($this->returnArgument(0))
        ;

        foreach ($aOxidConfig as $sKey => $sValue) {
            modConfig::getInstance()->setConfigParam($sKey, $sValue);
        }
        $aResult = $oMarmShopgate->_getConfigForFramework();
        $this->assertTrue(is_array($aResult));
        $this->assertEquals($aOxidConfig['customer_number'], $aResult['customer_number']);
        $this->assertEquals($aOxidConfig['shop_number'], $aResult['shop_number']);
        $this->assertEquals($aOxidConfig['apikey'], $aResult['apikey']);
        $this->assertEquals('oxid', $aResult['plugin']);
    }

    public function test_getConfigForAdminGui()
    {
        $aRequiredKeys = array(
            'customer_number' => false,
            'shop_number' => 'checkbox',
            'apikey' => 'input',
            'plugin' => false,
            'api_url' => false
        );
        $aOxidConfig = array(
            'marm_shopgate_customer_number' => 123321,
            'marm_shopgate_shop_number' => 54321,
            'marm_shopgate_apikey' => 'asdffdassfd',
            'marm_shopgate_api_url' => false
        );

        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                '_getConfig',
                'init',
                'getOxidConfigKey'
            )
        );
        $oMarmShopgate
            ->expects($this->once())
            ->method('_getConfig')
            ->will($this->returnValue($aRequiredKeys))
        ;
        $oMarmShopgate
            ->expects($this->once())
            ->method('init')
        ;
        $oMarmShopgate
            ->expects($this->atLeastOnce())
            ->method('getOxidConfigKey')
            ->will($this->returnValue('marmshopgateconfigkey'));
        foreach ($aOxidConfig as $sKey => $sValue) {
            modConfig::getInstance()->setConfigParam($sKey, $sValue);
        }
        $aResult = $oMarmShopgate->getConfigForAdminGui();
        $this->assertArrayNotHasKey('plugin', $aResult);
        $this->assertEquals('https://api.shopgate.com/shopgateway/api/', $aResult['api_url']['value']);
        $this->assertEquals('input', $aResult['apikey']['type']);
        $this->assertEquals('checkbox', $aResult['shop_number']['type']);
        $this->assertEquals('marmshopgateconfigkey', $aResult['apikey']['oxid_name']);
    }

    public function test_getOxidConfigKey()
    {
        $oMarmShopgate = $this->getProxyClass('marm_shopgate');
        $this->assertEquals('marm_shopgate_ef3d8d', $oMarmShopgate->getOxidConfigKey('customer_number'));
        $this->assertEquals('marm_shopgate_8f5c42', $oMarmShopgate->getOxidConfigKey('shop_number'));
        $this->assertEquals('marm_shopgate_9af98d', $oMarmShopgate->getOxidConfigKey('APIKEY'));
        $this->assertEquals('marm_shopgate_9af98d', $oMarmShopgate->getOxidConfigKey('apikey'));
    }

    public function test_getMobileSnippet()
    {
        $aOxidConfig = array(
            'disabled_mobile' => false,
            'enabled_mobile' => true,
            'shopgate_shop_number' => '123321'
        );
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                'getOxidConfigKey'
            )
        );
        $oMarmShopgate
            ->expects($this->at(0))
            ->method('getOxidConfigKey')
            ->with('enable_mobile_website')
            ->will($this->returnValue('disabled_mobile'))
        ;
        $oMarmShopgate
            ->expects($this->at(1))
            ->method('getOxidConfigKey')
            ->with('enable_mobile_website')
            ->will($this->returnValue('enabled_mobile'))
        ;
        $oMarmShopgate
            ->expects($this->at(2))
            ->method('getOxidConfigKey')
            ->with('shop_number')
            ->will($this->returnValue('shopgate_shop_number'))
        ;
        foreach ($aOxidConfig as $sKey => $sValue) {
            modConfig::getInstance()->setConfigParam($sKey, $sValue);
        }

        $this->assertEquals('', $oMarmShopgate->getMobileSnippet());
        $aSecondResult = $oMarmShopgate->getMobileSnippet();
        $this->assertContains($aOxidConfig['shopgate_shop_number'], $aSecondResult);
        $this->assertContains('shopgate.com', $aSecondResult);

    }

}