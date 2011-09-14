<?php

/**
 * Admin controller for Shopgate config tab
 */
class marm_shopgate_config extends Shop_Config
{
    /**
     * shopgate configuration template
     * @var string
     */
    protected $_sThisTemplate = "marm_shopgate_config.tpl";

    /**
     * stores array for shopgate config, with information how to display it
     * @var array
     */
    protected $_aShopgateConfig = null;

    /**
     * returns shopgate config array with information how to display it
     * @see marm_shopgate::getConfigForAdminGui()
     * @param bool $blReset
     * @return array
     */
    public function getShopgateConfig($blReset = false)
    {
        if ($this->_aShopgateConfig !== null && !$blReset) {
            return $this->_aShopgateConfig;
        }

        $this->_aShopgateConfig = marm_shopgate::getInstance()->getConfigForAdminGui();
        return $this->_aShopgateConfig;
    }
}