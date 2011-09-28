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