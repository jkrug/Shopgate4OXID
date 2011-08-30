<?php
class marm_shopgate_api extends oxUBase
{
    /**
     * For performance.
     * no parent::init call, so no components and other objects created
     * @return void
     */
    public function init(){}

    public function render()
    {
        $oShopgateFramework = marm_shopgate::getInstance()->getFramework();
        $oShopgateFramework->start();

        oxUtils::getInstance()->showMessageAndExit('');
    }
}