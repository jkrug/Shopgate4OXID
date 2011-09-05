<?php


class ShopgatePlugin extends ShopgatePluginCore {

    const MULTI_SEPERATOR = '||';
    const PARENT_SEPERATOR = '=>';

    protected $_sCurrency = 'EUR';
    protected $_blOxidUsesStock = true;

    
    public function startup() 
    {
        $oCur = oxConfig::getInstance()->getActShopCurrencyObject();
        $this->_sCurrency = $oCur->name;
        $this->_blOxidUsesStock = oxConfig::getInstance()->getConfigParam( 'blUseStock' );
        return true;
    }
    
    protected function createItemsCsv()
    {
//http://oxid.mskuodas-l.usr.nfq.lt/?cl=marm_shopgate_api&apikey=abcdefg&customer_number=123456&shop_number=12345&action=get_items_csv
        $sArticleTable = getViewName( 'oxarticles' );
        /** @var $oArticleList oxArticleList */
        $oArticleList = oxNew('oxArticleList');
        /** @var $oArticleBase oxArticle */
//        $oArticleBase = $oArticleList->getBaseObject();
        $oArticleBase = oxNew('oxArticle', array('_blUseLazyLoading' => false));

        $oArticleBase->setSkipAbPrice(true);
        $oArticleBase->setLoadParentData(false);
        $oArticleBase->setNoVariantLoading(true);

        $sFields = $oArticleBase->getSelectFields();
        $sSqlActiveSnippet = $oArticleBase->getSqlActiveSnippet();
        $sSelect = "
            SELECT
              {$sFields}
            FROM
              {$sArticleTable}
            WHERE
                {$sSqlActiveSnippet}
                AND {$sArticleTable}.oxparentid = ''
        ";
        $oArticleSaved = clone $oArticleBase;
        $rs = oxDb::getDb(true)->Execute( $sSelect);

        $iTime = microtime( true );
        if ($rs != false && $rs->recordCount() > 0) {
            while (!$rs->EOF) {
                $oArticle = clone $oArticleSaved;
                $oArticle->assign($rs->fields);

                $aItem = array();

                $aItem = $this->_loadRequiredFieldsForArticle($aItem, $oArticle);
                $aItem = $this->_loadAdditionalFieldsForArticle($aItem, $oArticle);

                $oArticle = null;
                $rs->moveNext();
                $this->addItem($aItem);
            }
        }
    }

    protected function _loadRequiredFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        $aItem['item_number']   = $oArticle->oxarticles__oxartnum->value;
        $aItem['item_name']     = $oArticle->oxarticles__oxtitle->value;
        $aItem['unit_amount']   = round($oArticle->getPrice()->getBruttoPrice(), 2);
        $aItem['description']   = $oArticle->getLongDesc();

        $aItem = $this->_loadPicturesForArticle($aItem, $oArticle);
        $aItem = $this->_loadCategoriesForArticle($aItem, $oArticle);

        if ($oArticle->isBuyable()) {
            $aItem['is_available']  = 1;
        }
        else {
            $aItem['is_available']  = 0;
        }
        $aItem = $this->_loadDeliveryTimeForArticle($aItem, $oArticle);
        $aItem = $this->_loadManufacturerForArticle($aItem, $oArticle);
        $aItem['url_deeplink']  = $oArticle->getLink();

        return $aItem;
    }

    protected function _loadPicturesForArticle(array $aItem, oxArticle $oArticle)
    {
        $aPictures = array();
        $aPicGallery = $oArticle->getPictureGallery();
        if ($aPicGallery['ZoomPic']) {
            foreach ($aPicGallery['ZoomPics'] as $aPic) {
                $aPictures[] = $aPic['file'];
            }
        }
        else {
            $aPictures[] = $aPicGallery['ActPic'];
        }
        $aItem['urls_images']   = implode('||', $aPictures);
        return $aItem;
    }


    protected function _loadCategoriesForArticle(array $aItem, oxArticle $oArticle)
    {
        $aCategoriesPath = $this->_getCategoriesPath();
        $aCategories = array();
        foreach ($oArticle->getCategoryIds() as $sCatId) {
            if (isset($aCategoriesPath[$sCatId])) {
                $aCategories[] = $aCategoriesPath[$sCatId];
            }
        }
        $aItem['categories'] = implode(self::MULTI_SEPERATOR, $aCategories);
        return $aItem;
    }

    protected $_aCategoriesPath = null;
    protected function _getCategoriesPath($blReset = false)
    {
        if ($this->_aCategoriesPath !== null && !$blReset) {
            return $this->_aCategoriesPath;
        }
        $sCategoriesTable = getViewName('oxcategories');
        $oLang = oxLang::getInstance();
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sLangAbbr = $oLang->getLanguageAbbr();
        if (strpos($sCategoriesTable, 'oxv_') !== false && strpos($sCategoriesTable, '_'.$sLangAbbr) !== false) {
            $sLangTag = '';
        }
        $sTitleField = 'OXTITLE'.$sLangTag;
        $sSQL = "
            SELECT
              OXID,
              {$sTitleField} as OXTITLE,
              OXPARENTID
            FROM
              {$sCategoriesTable}
            ORDER BY
              OXLEFT
        ";
        $aSqlRes = oxDb::getDb(true)->getAll($sSQL);
        foreach($aSqlRes as $aRes) {

            $sPath = '';
            if ($aRes['OXPARENTID'] != 'oxrootid') {
                $sPath = $this->_aCategoriesPath[$aRes['OXPARENTID']] . self::PARENT_SEPERATOR;
            }
            $this->_aCategoriesPath[$aRes['OXID']] = $sPath . $aRes['OXTITLE'];
        }
        return $this->_aCategoriesPath;
    }
    protected function _loadManufacturerForArticle(array $aItem, oxArticle $oArticle)
    {
        $aItem['manufacturer'] = '';
        $sManufacturerId = $oArticle->oxarticles__oxmanufacturerid->value;
        if ($sManufacturerId) {
            $aManufacturers = $this->_getManufacturers();
            if (isset($aManufacturers[$sManufacturerId])) {
                $aItem['manufacturer'] = $aManufacturers[$sManufacturerId];
            }
        }
        return $aItem;
    }

    protected $_aManufacturers = null;
    protected function _getManufacturers($blReset = false)
    {
        if ($this->_aManufacturers !== null && !$blReset) {
            return $this->_aManufacturers;
        }
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sTitleField = 'OXTITLE'.$sLangTag;
        $sManufacturersTable = getViewName('oxmanufacturers');
        $sSQL = '
            SELECT
              OXID,
              {$sTitleField} as OXTITLE
            FROM
              {$sManufacturersTable}
        ';
        $this->_aManufacturers = oxDb::getDb(true)->getCol($sSQL);
        if (!$this->_aManufacturers) {
            $this->_aManufacturers = array();
        }
        return $this->_aManufacturers;
    }

    protected function _loadDeliveryTimeForArticle(array $aItem, oxArticle $oArticle)
    {
        $sMinTime = $oArticle->oxarticles__oxmindeltime->value;
        $sMaxTime = $oArticle->oxarticles__oxmaxdeltime->value;
        $sTranslateIdent = 'DETAILS_' . $oArticle->oxarticles__oxdeltimeunit->value;
        $sText = '';
        if ($sMinTime && $sMinTime != $sMaxTime) {
            $sText .= $sMinTime .' - ';
        }
        if ($sMaxTime) {
            $sText .= $sMaxTime;
        }
        if ($sMaxTime > 1 || $sMinTime > 1) {
            $sTranslateIdent .='S';
        }
        $sText .= ' '.oxLang::getInstance()->translateString($sTranslateIdent);

        $aItem['available_text'] = $sText;
        return $aItem;
    }

    protected function _loadAdditionalFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        $aItem = $this->_loadAttributesForArticle($aItem, $oArticle);
        $aItem['manufacturer_item_number'] = $oArticle->oxarticles__oxmpn->value;
        $aItem['currency'] = $this->_sCurrency;
        $aItem['tax_percent'] = $oArticle->getArticleVat();
        $aItem['msrp'] = round($oArticle->getTPrice()->getBruttoPrice(), 2);

//        $aItem['shipping_costs_per_order'] = $oArticle->oxarticles__ox->value;
//        $aItem['additional_shipping_costs_per_unit'] = $oArticle->oxarticles__ox->value;

        if ((double) $oArticle->oxarticles__oxunitquantity->value && $oArticle->oxarticles__oxunitname->value) {
            $aItem['basic_price'] = round($oArticle->getPrice()->getBruttoPrice() / (double) $this->oxarticles__oxunitquantity->value, 2);
        }
        else {
            $aItem['basic_price'] = '';
        }

        if ($this->_blOxidUsesStock && $oArticle->oxarticles__oxstockflag->value != 4) {
            $aItem['use_stock'] = 1;
        }
        else {
            $aItem['use_stock'] = 0;
        }
        $aItem['stock_quantity'] = $oArticle->oxarticles__oxstock->value;
        $aItem['ean'] = $oArticle->oxarticles__oxean->value;
        $aItem['last_update'] = date('Y-m-d', strtotime($oArticle->oxarticles__oxtimestamp->value));
        $aItem['tags'] = $oArticle->getTags();
//        $aItem['sort_order'] = $oArticle->oxarticles__ox->value;
        $aItem['marketplace'] = $oArticle->oxarticles__marm_shopgate_marketplace->value;
//        $aItem['internal_order_info'] = $oArticle->oxarticles__ox->value;
//        $aItem['related_shop_item_numbers'] = $oArticle->oxarticles__ox->value;
//        $aItem['age_rating'] = $oArticle->oxarticles__ox->value;
        $aItem['weight'] = $oArticle->oxarticles__oxweight->value*1000;
        $aItem['is_free_shipping'] = $oArticle->oxarticles__oxfreeshipping->value;
        $aItem = $this->_loadAmountPricesForArticle($aItem, $oArticle);
//        $aItem['category_numbers'] = $oArticle->oxarticles__ox->value;
        return $aItem;
    }

    protected function _loadAmountPricesForArticle(array $aItem, oxArticle $oArticle)
    {
        $aPriceInfo = $oArticle->loadAmountPriceInfo();
        $aParsed = array();
        foreach ($aPriceInfo as $oPriceItem) {
            $aParsed[] = $oPriceItem->oxprice2article__oxamount->value . self::PARENT_SEPERATOR . $oPriceItem->fbrutprice;
        }
        $aItem['block_pricing'] = implode(self::MULTI_SEPERATOR, $aParsed);
        return $aItem;
    }

    protected function _loadAttributesForArticle(array $aItem, oxArticle $oArticle)
    {
        $aProcessedAttributes = array();
        $aAttributes = $oArticle->getAttributes();
        foreach ($aAttributes as $oAttribute) {
            $aProcessedAttributes[] = $oAttribute->oxattribute__oxtitle->value
                                      . self::PARENT_SEPERATOR
                                      . $oAttribute->oxattribute__oxvalue->value;
        }
        $aItem['properties'] = implode(MULTI_SEPERATOR, $aProcessedAttributes);
        return $aItem;
    }


    protected function createReviewsCsv()
    {

    }
    
    protected function createPagesCsv()
    {

    }
    
    public function getUserData($user, $pass)
    {

    }
    
    public function createShopInfo(){}

    public function saveOrder(ShopgateOrder $order)
    {
        
    }
}
