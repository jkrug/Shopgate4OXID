<?php

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