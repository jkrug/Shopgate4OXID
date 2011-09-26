<?php
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
