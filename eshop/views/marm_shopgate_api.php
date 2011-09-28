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