<?php

/**
 * Admin controller for shopgate tab in article list, so Shopgate values
 * can be set and edited.
 */
class marm_shopgate_article extends oxAdminDetails
{
    protected $_sThisTemplate = 'marm_shopgate_article.tpl';

    /**
     * stores active article for editing
     * @var oxArticle
     */
    protected $_oArticle = null;

    /**
     * returns active article for editing
     * @param bool $blReset
     * @return oxArticle
     */
    public function getArticle($blReset = false)
    {
        if ($this->_oArticle !== null && !$blReset) {
            return $this->_oArticle;
        }

        $soxId    = $this->getEditObjectId();
        $this->_oArticle = oxNewArticle($soxId);

        return $this->_oArticle;
    }

    /**
     * Saves changes of article parameters.
     *
     * @return null
     */
    public function save()
    {
        $soxId    = $this->getEditObjectId();
        $aParams  = oxConfig::getParameter( "editval" );

        $oArticle = oxNew( "oxarticle");
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->loadInLang( $this->_iEditLang, $soxId);
        $oArticle->setLanguage(0);

        $oArticle->assign( $aParams );
        $oArticle->setLanguage($this->_iEditLang);
        $oArticle->save();

        $this->setEditObjectId( $oArticle->getId() );
    }
}
