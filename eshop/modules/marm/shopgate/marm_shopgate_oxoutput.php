<?php

class marm_shopgate_oxoutput extends marm_shopgate_oxoutput_parent
{
    /**
     * if not admin, inject shopgate mobile snippet at ending body tag
     * @param $sOutput
     * @return mixed
     */
    public function marmReplaceBody( $sOutput )
    {
        if(!isAdmin()) {
            $sMobileSnippet = marm_shopgate::getInstance()->getMobileSnippet();
            $sOutput = str_ireplace("</body>", "{$sMobileSnippet}\n</body>", $sOutput);
        }
        return $sOutput;
    }

    /**
     * returns $sValue filtered by parent and marm_shopgate_oxoutput::marmReplaceBody
     * @param $sValue
     * @param $sClassName
     * @return mixed
     */
    public function process($sValue, $sClassName)
    {
        $sValue = parent::process($sValue, $sClassName);
        $sValue = $this->marmReplaceBody( $sValue);
        return $sValue;
    }

}