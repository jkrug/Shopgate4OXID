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

class unit_marm_shopgate_admin_marm_shopgate_configTest extends OxidTestCase
{
    protected $_oOldMarmShopgateInstance = null;

    public function tearDown()
    {
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
    }


    public function test_getShopgateConfig()
    {
        $aTestConfig1 = array(
            'key1' => 'value2',
            'key3' => 'value4'
        );
        $aTestConfig2 = $aTestConfig1;
        $aTestConfig2['key5'] = 'value6';

        $oMarmShopgateMock = $this->getMock(
            'marm_shopgate',
            array(
                'getConfigForAdminGui',
                'init'
            )
        );
        $oMarmShopgateMock
            ->expects($this->at(0))
            ->method('getConfigForAdminGui')
            ->will($this->returnValue($aTestConfig1))
        ;
        $oMarmShopgateMock
            ->expects($this->at(1))
            ->method('getConfigForAdminGui')
            ->will($this->returnValue($aTestConfig2))
        ;
        $this->_oOldMarmShopgateInstance = marm_shopgate::getInstance();
        marm_shopgate::replaceInstance($oMarmShopgateMock);
        
        $oView = oxNew('marm_shopgate_config');

        $this->assertEquals($aTestConfig1, $oView->getShopgateConfig());
        // cache
        $this->assertEquals($aTestConfig1, $oView->getShopgateConfig());
        // reset cache
        $this->assertEquals($aTestConfig2, $oView->getShopgateConfig(true));

    }
}