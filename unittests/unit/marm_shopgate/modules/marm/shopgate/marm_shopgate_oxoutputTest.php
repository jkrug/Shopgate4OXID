<?php

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';

class unit_marm_shopgate_modules_marm_shopgate_marm_shopgate_oxoutputTest extends OxidTestCase
{

    protected $_oOldMarmShopgateInstance = null;

    public function tearDown()
    {
        if ($this->_oOldMarmShopgateInstance !== null) {
            marm_shopgate::replaceInstance($this->_oOldMarmShopgateInstance);
            $this->_oOldMarmShopgateInstance = null;
        }
    }

    public function test_marmReplaceBody()
    {
        $oModule = $this->getProxyClass('marm_shopgate_oxoutput');
        $oMarmShopgateMock = $this->getMock(
            'marm_shopgate',
            array(
                'getMobileSnippet'
            )
        );
        $sSnippet = 'html snippet here';
        $oMarmShopgateMock
            ->expects($this->once())
            ->method('getMobileSnippet')
            ->will($this->returnValue($sSnippet))
        ;
        $this->_oOldMarmShopgateInstance = marm_shopgate::getInstance();
        marm_shopgate::replaceInstance($oMarmShopgateMock);

        $sResult = $oModule->marmReplaceBody('<html><body></body></html>');
        $this->assertContains($sSnippet, $sResult);
        $this->assertContains('<html>', $sResult);
    }

    public function test_process()
    {
        $sInput = '<html><head></head><body></body></html>';
        $sOutput = '<html><head></head><body><script type="text/javascript"></script></body></html>';
        $oModule = $this->getMock(
            'marm_shopgate_oxoutput',
            array(
                'marmReplaceBody'
            )
        );
        $oModule
            ->expects($this->once())
            ->method('marmReplaceBody')
            ->with($sInput)
            ->will($this->returnValue($sOutput))
        ;
        $this->assertEquals($sOutput, $oModule->process($sInput, 'classname'));

    }
}