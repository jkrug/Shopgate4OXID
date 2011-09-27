<?php
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