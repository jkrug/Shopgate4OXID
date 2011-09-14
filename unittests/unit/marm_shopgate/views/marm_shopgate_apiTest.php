<?php

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