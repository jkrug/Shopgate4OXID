<?php

require_once 'unit/OxidTestCase.php';
require_once 'unit/test_config.inc.php';


class unit_marm_shopgate_admin_marm_shopgate_articleTest extends OxidTestCase
{
    protected function tearDown()
    {
        modInstances::cleanup();
        oxTestModules::cleanUp();
    }

    public function test_getArticle()
    {
        $sFirstId = 'superid';
        $oFirstObject = new stdClass();
        $oFirstObject->someValue = 'first data';
        $sSecondId = 'duperid';
        $oSecondObject = new stdClass();
        $oSecondObject->anotherValue = 'other info';
        $oView = $this->getMock(
            'marm_shopgate_article',
            array(
                 'getEditObjectId'
            )
        );
        $oView
            ->expects($this->at(0))
            ->method('getEditObjectId')
            ->will($this->returnValue($sFirstId))
        ;
        $oView
            ->expects($this->at(1))
            ->method('getEditObjectId')
            ->will($this->returnValue($sSecondId))
        ;

        $oModUtlisObject = $this->getMock(
            'oxUtilsObject',
            array(
                'oxNewArticle'
            )
        );
        $oModUtlisObject
            ->expects($this->at(0))
            ->method('oxNewArticle')
            ->with($sFirstId)
            ->will($this->returnValue($oFirstObject))
        ;
        $oModUtlisObject
            ->expects($this->at(1))
            ->method('oxNewArticle')
            ->with($sSecondId)
            ->will($this->returnValue($oSecondObject))
        ;

        modInstances::addMod('oxUtilsObject', $oModUtlisObject);
        $this->assertEquals($oFirstObject, $oView->getArticle());
        $this->assertEquals($oFirstObject, $oView->getArticle());
        $this->assertEquals($oSecondObject, $oView->getArticle(true));
    }

    public function test_save()
    {
        $aRequestData = array(
            'firstkey' => 'firstvalue',
            'seckey' => 'secVal'
        );
        $sSavingId = 'first';
        $sAfterSaveId = 'second';

        modConfig::setParameter('editval',$aRequestData);

        $oView = $this->getMock(
            'marm_shopgate_article',
            array(
                'getEditObjectId',
                'setEditObjectId'
            )
        );
        $oView
            ->expects($this->once())
            ->method('getEditObjectId')
            ->will($this->returnValue($sSavingId))
        ;
        $oView
            ->expects($this->once())
            ->method('setEditObjectId')
            ->with($sAfterSaveId)
        ;
        $oNewArticle = $this->getMock(
            'oxArticle',
            array(
                'setLanguage',
                'loadInLang',
                'assign',
                'save',
                'getId'
            )
        );
        $oNewArticle
            ->expects($this->exactly(3))
            ->method('setLanguage')
        ;
        $oNewArticle
            ->expects($this->once())
            ->method('loadInLang')
        ;
        $oNewArticle
            ->expects($this->once())
            ->method('assign')
            ->with($aRequestData);
        ;
        $oNewArticle
            ->expects($this->once())
            ->method('save')
        ;
        $oNewArticle
            ->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($sAfterSaveId))
        ;
        oxTestModules::addModuleObject('oxArticle', $oNewArticle);
        
        $oView->save();
    }
}