<?php
/**
 * Frontend controller for handling Shopgate integration requests
 */
class marm_shopgate_api extends oxUBase
{
    /**
     * For performance.
     * no parent::init call, so no components and other objects created
     * @return void
     */
    public function init(){}

    /**
     * Loads framework, and executes start action in it.
     * After this, oxid will exit without template rendering ( oxUtils::showMessageAndExit())
     * @return void
     */
    public function render()
    {
        $oShopgateFramework = marm_shopgate::getInstance()->getFramework();
        $oShopgateFramework->start();

        oxUtils::getInstance()->showMessageAndExit('');
    }
}