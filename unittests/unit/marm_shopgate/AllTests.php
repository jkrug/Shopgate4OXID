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

/**
 * This file contains the script required to run all tests in unit dir.
 * This file is supposed to be executed over PHPUnit framework
 * It is called something like this:
 * phpunit <Test dir>_AllTests
 */

require_once 'PHPUnit/Framework/TestSuite.php';

/**
 * PHPUnit_Framework_TestCase implemetnation for adding and testing all tests from unit dir
 */
class unit_marm_shopgate_AllTests extends PHPUnit_Framework_TestCase
{

    static function suite()
    {

        $suite = new PHPUnit_Framework_TestSuite('PHPUnit');

//        require_once( 'unit/marm_shopgate/shopgate/plugins/plugin_oxidTest.php');
        $aFiles = array(
//            'unit/marm_shopgate/admin/marm_shopgate_articleTest.php',
//            'unit/marm_shopgate/admin/marm_shopgate_configTest.php',
//            'unit/marm_shopgate/core/marm_shopgateTest.php',
//            'unit/marm_shopgate/modules/marm/shopgate/marm_shopgate_oxoutputTest.php',
//            'unit/marm_shopgate/modules/marm/shopgate/marm_shopgate_oxorderTest.php',
//            'unit/marm_shopgate/shopgate/plugins/plugin_oxidTest.php',
//            'unit/marm_shopgate/views/marm_shopgate_apiTest.php',
        );
        if (count($aFiles)) {
            foreach ($aFiles as $sFilename) {
                require_once( $sFilename);
                $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
                if ( class_exists( $sFilename ) ) {
                    $suite->addTestSuite( $sFilename );
                }
            }
            return $suite;
        }

        //adding UNIT Tests
        foreach( glob("unit/marm_shopgate/admin/*Test.php") as $sFilename) {
            require_once( $sFilename);
            $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
            if ( class_exists( $sFilename ) )
                $suite->addTestSuite( $sFilename );
        }

        foreach( glob("unit/marm_shopgate/core/*Test.php") as $sFilename) {
            require_once( $sFilename);
            $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
            if ( class_exists( $sFilename ) )
                $suite->addTestSuite( $sFilename );
        }

        foreach( glob("unit/marm_shopgate/modules/marm/shopgate/*Test.php") as $sFilename) {
            require_once( $sFilename);
            $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
            if ( class_exists( $sFilename ) )
                $suite->addTestSuite( $sFilename );
        }

        foreach( glob("unit/marm_shopgate/shopgate/plugins/*Test.php") as $sFilename) {
            require_once( $sFilename);
            $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
            if ( class_exists( $sFilename ) )
                $suite->addTestSuite( $sFilename );
        }

        foreach( glob("unit/marm_shopgate/views/*Test.php") as $sFilename) {
            require_once( $sFilename);
            $sFilename = ''.str_replace("/", "_", str_replace( array( ".php"), "", $sFilename));
            if ( class_exists( $sFilename ) )
                $suite->addTestSuite( $sFilename );
        }

        return $suite;
    }
}
