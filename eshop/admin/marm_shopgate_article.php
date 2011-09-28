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
