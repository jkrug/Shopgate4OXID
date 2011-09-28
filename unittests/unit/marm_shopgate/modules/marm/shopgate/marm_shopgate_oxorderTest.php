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

class unit_marm_shopgate_modules_marm_shopgate_marm_shopgate_oxorderTest extends OxidTestCase
{

    protected $_oOldMarmShopgateInstance = null;

    public function tearDown()
    {
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
    }

    public function test__marmCheckSendDate()
    {
        $iShopgateOrderId = 1239876541;
        $oTestingOrder = $this->getProxyClass('marm_shopgate_oxorder');
        $oMarmShopgateMock = $this->getMock(
            'marm_shopgate',
            array(
                'setOrderShippingCompleted'
            )
        );
        $oMarmShopgateMock
            ->expects($this->once())
            ->method('setOrderShippingCompleted')
            ->with($iShopgateOrderId)
        ;
        $this->_oOldMarmShopgateInstance = marm_shopgate::getInstance();
        marm_shopgate::replaceInstance($oMarmShopgateMock);

        // only one of calls will execute marm_shopgate::setOrderShippingCompleted

        $oTestingOrder->oxorder__oxsenddate = new oxField('', oxField::T_RAW);
        $oTestingOrder->oxorder__marm_shopgate_order_number = new oxField('', oxField::T_RAW);
        $oTestingOrder->_marmCheckSendDate();

        $oTestingOrder->oxorder__oxsenddate = new oxField(date('Y-m-d'), oxField::T_RAW);
        $oTestingOrder->oxorder__marm_shopgate_order_number = new oxField('', oxField::T_RAW);
        $oTestingOrder->_marmCheckSendDate();

        $oTestingOrder->oxorder__oxsenddate = new oxField('', oxField::T_RAW);
        $oTestingOrder->oxorder__marm_shopgate_order_number = new oxField($iShopgateOrderId, oxField::T_RAW);
        $oTestingOrder->_marmCheckSendDate();

        $oTestingOrder->oxorder__oxsenddate = new oxField(date('Y-m-d H:i:s'), oxField::T_RAW);
        $oTestingOrder->oxorder__marm_shopgate_order_number = new oxField($iShopgateOrderId, oxField::T_RAW);
        $oTestingOrder->_marmCheckSendDate();
    }

    public function test_save()
    {
        $oTestingOrder = $this->getMock(
            $this->getProxyClassName('marm_shopgate_oxorder'),
            array(
                '_marmCheckSendDate'
            )
        );
        $oTestingOrder
            ->expects($this->once())
            ->method('_marmCheckSendDate')
        ;
        // to avoid inserting anything to DB
        // see oxbase::save()
        $oTestingOrder->setNonPublicVar('_aFieldNames', false);

        $this->assertFalse($oTestingOrder->save());

    }
}