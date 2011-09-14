<?php

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