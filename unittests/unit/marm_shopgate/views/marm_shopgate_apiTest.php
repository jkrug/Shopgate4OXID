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

class unit_marm_shopgate_views_marm_shopgate_apiTest extends OxidTestCase
{
    protected $_oOldMarmShopgateInstance = null;

    public function tearDown()
    {
        modInstances::cleanup();
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
    }

    public function test_init()
    {
        // test for NOT loading components
        $oView = oxNew('marm_shopgate_api');
        $this->assertNull($oView->init());
        $this->assertEquals(array(), $oView->getComponents());
    }

    public function test_render()
    {
        $oFramework = $this->getMock(
            'stdClass',
            array(
                'start'
            )
        );
        $oMarmShopgateMock = $this->getMock(
            'marm_shopgate',
            array(
                'getFramework',
            )
        );
        $oMarmShopgateMock
            ->expects($this->once())
            ->method('getFramework')
            ->will($this->returnValue($oFramework))
        ;
        $this->_oOldMarmShopgateInstance = marm_shopgate::getInstance();
        marm_shopgate::replaceInstance($oMarmShopgateMock);

        $oOxUtils = $this->getMock(
            'oxUtils',
            array('showMessageAndExit')
        );
        $oOxUtils
            ->expects($this->once())
            ->method('showMessageAndExit')
        ;
        modInstances::addMod('oxUtils', $oOxUtils);

        $oView = oxNew('marm_shopgate_api');

        $this->assertNull( $oView->render());

    }
}