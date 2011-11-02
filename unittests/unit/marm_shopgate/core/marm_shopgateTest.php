<?php
/**
 * Shopgate Connector
 *
 * Copyright (c) 2011 Joscha Krug | marmalade.de
 * E-mail: mail@marmalade.de
 * http://www.marmalade.de
 *
 * Developed for
 * Shopgate GmbH
 * www.shopgate.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';

class unit_marm_shopgate_core_marm_shopgateTest extends OxidTestCase
{
    protected $_oOldMarmShopgateInstance = null;
    protected $_blResetOxConfig  = false;
    protected $_blResetModules   = false;

    public function tearDown()
    {
        if ($this->_blResetOxConfig) {
            $this->_blResetOxConfig = false;
            modConfig::$unitMOD = null;
        }
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
        unset($_GLOBAL['marm_shopgate_include_counter']);
        if ($this->_blResetModules) {
            $this->_blResetModules = false;
            oxTestModules::cleanUp();
        }

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
        file_put_contents($sFilePath1, '<?php
/**
 * Shopgate Connector
 *
 * Copyright (c) 2011 Joscha Krug | marmalade.de
 * E-mail: mail@marmalade.de
 * http://www.marmalade.de
 *
 * Developed for
 * Shopgate GmbH
 * www.shopgate.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

 $GLOBALS[\'marm_shopgate_include_counter\'] += 1;');
        file_put_contents($sFilePath2, '<?php
/**
 * Shopgate Connector
 *
 * Copyright (c) 2011 Joscha Krug | marmalade.de
 * E-mail: mail@marmalade.de
 * http://www.marmalade.de
 *
 * Developed for
 * Shopgate GmbH
 * www.shopgate.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

 ;$GLOBALS[\'marm_shopgate_include_counter\'] += 2;');
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                '_getFilesToInclude',
                '_getLibraryDir',
                'initConfig'
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
            ->method('initConfig')
        ;
        $GLOBALS['marm_shopgate_include_counter'] = 0;

        $oMarmShopgate->init();

        $this->assertEquals(3, $GLOBALS['marm_shopgate_include_counter']);

        // test for double call (require_once should include only one time, so counter will be not touched)
        $GLOBALS['marm_shopgate_include_counter'] = 9;
        $oMarmShopgate->init();
        $this->assertEquals(9, $GLOBALS['marm_shopgate_include_counter']);
        
    }

    public function test_initConfig()
    {
        $aMockConfig = array('plugin' => 'oxid');
        $oMarmShopgate = $this->getMock(
            $this->getProxyClassName('marm_shopgate'),
            array(
                '_getConfigForFramework'
            )
        );

        $oMarmShopgate
            ->expects($this->at(0))
            ->method('_getConfigForFramework')
            ->will($this->returnValue($aMockConfig))
        ;

        $aMockConfig = array(
            'plugin' => 'oxid',
            'apikey' => '09602cddb5af0aba745293d08ae6bcf6',
            'customer_number' => '12345',
            'shop_number' => '12345'
        );
        $oMarmShopgate
            ->expects($this->at(1))
            ->method('_getConfigForFramework')
            ->will($this->returnValue($aMockConfig))
        ;
        $oMarmShopgate->initConfig();
        $this->assertNull(ShopgateConfig::$aLastSetConfig);
        $oMarmShopgate->initConfig();
        $this->assertEquals($aMockConfig, ShopgateConfig::$aLastSetConfig[0]);
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
        $sDefaultGroup = 'general';
        $aRequiredKeys = array(
            'customer_number' => array('type' => 'input', 'group' => $sDefaultGroup),
            'shop_number' => array('type' => 'checkbox', 'group' => $sDefaultGroup),
            'apikey' => array('type' => 'input', 'group' => $sDefaultGroup),
            'plugin' => array('type' => false, 'group' => $sDefaultGroup),
            'api_url' => array('type' => 'input', 'group' => $sDefaultGroup)
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
        $this->assertArrayNotHasKey('plugin', $aResult[$sDefaultGroup]);
        $this->assertEquals('https://api.shopgate.com/shopgateway/api/', $aResult[$sDefaultGroup]['api_url']['value']);
        $this->assertEquals('input', $aResult[$sDefaultGroup]['apikey']['type']);
        $this->assertEquals('checkbox', $aResult[$sDefaultGroup]['shop_number']['type']);
        $this->assertEquals('marmshopgateconfigkey', $aResult[$sDefaultGroup]['apikey']['oxid_name']);
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

    public function test_getMobileSnippet_with_details()
    {
        $aOxidConfig = array(
            'enabled_mobile' => true,
            'shopgate_shop_number' => '123321'
        );
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                'getOxidConfigKey',
                '_getDetailsMobileSnippet'
            )
        );
        $oMarmShopgate
            ->expects($this->at(0))
            ->method('getOxidConfigKey')
            ->with('enable_mobile_website')
            ->will($this->returnValue('enabled_mobile'))
        ;
        $oMarmShopgate
            ->expects($this->at(1))
            ->method('getOxidConfigKey')
            ->with('shop_number')
            ->will($this->returnValue('shopgate_shop_number'))
        ;
        $oMarmShopgate
            ->expects($this->at(2))
            ->method('_getDetailsMobileSnippet')
            ->will($this->returnValue(''))
        ;
        $oMarmShopgate
            ->expects($this->at(3))
            ->method('getOxidConfigKey')
            ->with('enable_mobile_website')
            ->will($this->returnValue('enabled_mobile'))
        ;
        $oMarmShopgate
            ->expects($this->at(4))
            ->method('getOxidConfigKey')
            ->with('shop_number')
            ->will($this->returnValue('shopgate_shop_number'))
        ;
        $oMarmShopgate
            ->expects($this->at(5))
            ->method('_getDetailsMobileSnippet')
            ->will($this->returnValue('shopgate_item_number_here'));
        foreach ($aOxidConfig as $sKey => $sValue) {
            modConfig::getInstance()->setConfigParam($sKey, $sValue);
        }

        $sFirstResult =  $oMarmShopgate->getMobileSnippet();
        $this->assertContains($aOxidConfig['shopgate_shop_number'], $sFirstResult);
        $this->assertNotContains('shopgate_item_number_here', $sFirstResult );
        $aSecondResult = $oMarmShopgate->getMobileSnippet();
        $this->assertContains($aOxidConfig['shopgate_shop_number'], $aSecondResult);
        $this->assertContains('shopgate_item_number_here', $aSecondResult);

    }

    public function test__getDetailsMobileSnippet()
    {
        $oFakeArticle = new stdClass();
        $oFakeArticle->oxarticles__oxartnum = new stdClass();
        $oFakeArticle->oxarticles__oxartnum->value = 'artnumhere';

        $oViewMock = $this->getMock(
            'oxView',
            array(
                'getClassName',
                'getProduct'
            )
        );
        $oViewMock
            ->expects($this->at(0))
            ->method('getClassName')
            ->will($this->returnValue('list'))
        ;
        $oViewMock
            ->expects($this->at(1))
            ->method('getClassName')
            ->will($this->returnValue('details'))
        ;
        $oViewMock
            ->expects($this->atLeastOnce())
            ->method('getProduct')
            ->will($this->returnValue($oFakeArticle))
        ;
        $oConfigMock = $this->getMock(
            'oxConfig',
            array(
                'getActiveView'
            )
        );
        $oConfigMock
            ->expects($this->exactly(2))
            ->method('getActiveView')
            ->will($this->returnValue($oViewMock))
        ;
        $this->_blResetOxConfig = true;
        modConfig::$unitMOD = $oConfigMock;

        $oMarmShopgate = $this->getProxyClass('marm_shopgate');

        $this->assertEquals('', $oMarmShopgate->_getDetailsMobileSnippet());
        $sResult = $oMarmShopgate->_getDetailsMobileSnippet();
        $this->assertContains($oFakeArticle->oxarticles__oxartnum->value, $sResult);
        $this->assertContains('item', $sResult);
        $this->assertContains('redirect', $sResult);
        $this->assertContains('item_number', $sResult);

    }

    public function test_setOrderShippingCompleted()
    {
        $iShopgateOrderId = '1223321123';
        $sExceptionMessage = 'testmessage';
        $oShopgateFrameworkException = new ShopgateFrameworkException('testmessage');
        $oMarmShopgate = $this->getMock(
            'marm_shopgate',
            array(
                 'init'
            )
        );
        $oMarmShopgate
                ->expects($this->atLeastOnce())
                ->method('init')
        ;
        $oShopgateOrderApiMock = $this->getMock(
            'ShopgateOrderApi',
            array(
                'setShippingComplete'
            )
        );
        $oShopgateOrderApiMock
            ->expects($this->at(0))
            ->method('setShippingComplete')
            ->with($iShopgateOrderId)
            ->will($this->returnValue(null))
        ;
        $this->_blResetModules = true;
        oxTestModules::addModuleObject('ShopgateOrderApi', $oShopgateOrderApiMock);

        // normal call
        $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);

        //exceptions
        $oShopgateOrderApiMock
                ->expects($this->any())
                ->method('setShippingComplete')
                ->with($iShopgateOrderId)
                ->will($this->throwException($oShopgateFrameworkException))
        ;
        try {
            $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
            $this->fail('exception expected');
        }
        catch (oxException $oEx){
            $this->assertEquals($sExceptionMessage, $oEx->getMessage());
        }
        $oShopgateFrameworkException->lastResponse = 'string';
        try {
            $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
            $this->fail('exception expected');
        }
        catch (oxException $oEx){
            $this->assertEquals($sExceptionMessage, $oEx->getMessage());
        }
        $oShopgateFrameworkException->lastResponse = array();
        try {
            $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
            $this->fail('exception expected');
        }
        catch (oxException $oEx){
            $this->assertEquals($sExceptionMessage, $oEx->getMessage());
        }
        $oShopgateFrameworkException->lastResponse = array('error'=>202);
        try {
            $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
            $this->fail('exception expected');
        }
        catch (oxException $oEx){
            $this->assertEquals($sExceptionMessage, $oEx->getMessage());
        }

        // do not react to errors 203 and 204
        $oShopgateFrameworkException->lastResponse = array('error'=>204);
        $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
        $oShopgateFrameworkException->lastResponse = array('error'=>203);
        $oMarmShopgate->setOrderShippingCompleted($iShopgateOrderId);
    }

}