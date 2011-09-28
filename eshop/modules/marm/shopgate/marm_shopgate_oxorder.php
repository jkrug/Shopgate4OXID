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

class marm_shopgate_oxorder extends marm_shopgate_oxorder_parent
{
    /**
     * executes parent save, calls marmCheckSendDate, returns parent result
     * @return boolean
     */
    public function save()
    {
        $blResult = parent::save();
        $this->_marmCheckSendDate();
        return $blResult;
    }

    /**
     * if order send date is set, and order is from shopgate, 
     * call marm_shopgate::setOrderShippingCompleted()
     * @return void
     */
    protected function _marmCheckSendDate()
    {
        $iTime = strtotime($this->oxorder__oxsenddate->value);
        $iShopgateOrderId = $this->oxorder__marm_shopgate_order_number->value;
        if ($iTime > 0 && $iShopgateOrderId) {
            marm_shopgate::getInstance()->setOrderShippingCompleted($iShopgateOrderId);
        }
    }
}