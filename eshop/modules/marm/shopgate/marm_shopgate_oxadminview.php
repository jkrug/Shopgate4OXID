<?php
/**
 * this will emulate oxid 4.5.x  oxAdminView::getEditObjectId()
 */
class marm_shopgate_oxadminview extends marm_shopgate_oxadminview_parent
{

    /**
     * Editable object id
     *
     * @var string
     */
    protected $_sEditObjectId = null;

    /**
     * Returns active/editable object id
     *
     * @return string
     */
    public function getEditObjectId()
    {
        if ( null === ( $sId = $this->_sEditObjectId ) ) {
            if ( null === ( $sId = oxConfig::getParameter( "oxid" ) ) ) {
                $sId = oxSession::getVar( "saved_oxid" );
            }
        }
        return $sId;
    }

    /**
     * Sets editable object id
     *
     * @param string $sId object id
     *
     * @return string
     */
    public function setEditObjectId( $sId )
    {
        $this->_sEditObjectId = $sId;
        $this->_aViewData["updatelist"] = 1;
    }
}
