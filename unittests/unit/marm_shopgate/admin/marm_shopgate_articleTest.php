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