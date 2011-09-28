<?php


class ShopgatePlugin extends ShopgatePluginCore {

    const MULTI_SEPERATOR = '||';
    const PARENT_SEPERATOR = '=>';

    protected $_sVariantSeparator = " | ";

    /**
     * stores active currency name.
     * example: EUR
     * @var string
     */
    protected $_sCurrency = null;
    
    protected $_blOxidUsesStock = true;

    /**
     * stores main loaders for article export
     * @var array
     */
    protected $_aMainItemLoaders = array(
        '_loadRequiredFieldsForArticle',
        '_loadAdditionalFieldsForArticle',
        '_loadSelectionListForArticle',
        '_loadVariantsInfoForArticle',
        '_loadPersParamForArticle'
    );

    /**
     * stores required field loaders for article export
     * @var array
     */
    protected $_aRequiredItemFieldLoaders = array(
        '_loadArticleExport_item_number',
        '_loadArticleExport_item_name',
        '_loadArticleExport_unit_amount',
        '_loadArticleExport_description',
        '_loadArticleExport_url_images',
        '_loadArticleExport_categories',
        '_loadArticleExport_is_available',
        '_loadArticleExport_available_text',
        '_loadArticleExport_manufacturer',
        '_loadArticleExport_url_deeplink'
    );

    /**
     * stores additional field loaders for article export
     * @var array
     */
    protected $_aAdditionalItemFieldLoaders = array(
        '_loadArticleExport_properties',
        '_loadArticleExport_manufacturer_item_number',
        '_loadArticleExport_currency',
        '_loadArticleExport_tax_percent',
        '_loadArticleExport_msrp',
        '_loadArticleExport_basic_price',
        '_loadArticleExport_use_stock',
        '_loadArticleExport_stock_quantity',
        '_loadArticleExport_ean',
        '_loadArticleExport_last_update',
        '_loadArticleExport_tags',
        '_loadArticleExport_marketplace',
        '_loadArticleExport_weight',
        '_loadArticleExport_is_free_shipping',
        '_loadArticleExport_block_pricing'
    );

    /**
     * stores variant field loaders for article export
     * @var array
     */
    protected $_aVariantFieldLoaders = array(
        '_loadArticleExport_has_children',
        '_loadArticleExport_parent_item_number',
        '_loadArticleExport_attribute'
    );

    protected $_aOrderImportLoaders = array(
        '_loadOrderPrice',
        '_loadOrderRemark',
        '_loadOrderContacts',
        '_loadOrderAdditionalInfo'
    );

    /**
     * associated array of categories paths
     * array(category id => category path)
     * @var array
     */
    protected $_aCategoriesPath = null;

    /**
     * stores associated array with manufacturers id and names
     * array(OXID=>oxtitle, OXID=>oxtitle)
     * @var array
     */
    protected $_aManufacturers = null;

    /**
     * loads variables that are used in runtime
     * @return bool true
     */
    public function startup()
    {
        $this->_blOxidUsesStock = oxConfig::getInstance()->getConfigParam( 'blUseStock' );
        return true;
    }
    
    /**
     * formats oxarticle object for list usage. will be used to clone and load data from DB
     * @return oxArticle
     */
    protected function _getArticleBase()
    {
        /** @var $oArticleBase oxArticle */
        $oArticleBase = oxNew('oxArticle', array('_blUseLazyLoading' => false));

        $oArticleBase->setSkipAbPrice(true);
        $oArticleBase->setLoadParentData(false);
        $oArticleBase->setNoVariantLoading(true);
        return $oArticleBase;
    }

    /**
     * Formats SQL from oxarticle object to load the list
     * @param oxArticle $oArticleBase
     * @return string
     */
    protected function _getArticleSQL($oArticleBase)
    {
        $sArticleTable = $oArticleBase->getViewName();
        $sFields = $oArticleBase->getSelectFields();
        $sSqlActiveSnippet = $oArticleBase->getSqlActiveSnippet();

        $sSelect = " SELECT {$sFields} FROM {$sArticleTable} WHERE {$sSqlActiveSnippet} ";
        return $sSelect;
    }

    /**
     * checks compatibility with ShopgateFramework default row method
     * remove then requirements will change to new version
     * @param ShopgatePlugin $oShopgatePlugin
     * @return array
     * @deprecated
     */
    protected function getDefaultRowForCreateItemsCsv($oShopgatePlugin)
    {
        $aDefaultRow = array();
        if (method_exists($oShopgatePlugin, 'buildDefaultProductRow')) {
            $aDefaultRow = $oShopgatePlugin->buildDefaultProductRow();
        }
        elseif(method_exists($oShopgatePlugin, 'buildDefaultRow')) {
            $aDefaultRow = $oShopgatePlugin->buildDefaultRow();
        }
        return $aDefaultRow;
    }

    /**
     * loads default row ($this->buildDefaultRow()) and
     * for each article will overwrite data, and pass to $this->addItem()
     * @return void
     */
    protected function createItemsCsv()
    {
        $oArticleBase = $this->_getArticleBase();

        $sSelect = $this->_getArticleSQL($oArticleBase);

        $aDefaultRow = $this->getDefaultRowForCreateItemsCsv($this);
        $aMainLoaders = $this->_getMainItemLoaders();

        $rs = oxDb::getDb(true)->Execute( $sSelect);
        if ($rs != false && $rs->recordCount() > 0) {
            while (!$rs->EOF) {
                $oArticle = clone $oArticleBase;
                $oArticle->assign($rs->fields);

                $aItem = $aDefaultRow;

                $aItem = $this->_executeLoaders($aMainLoaders, $aItem, $oArticle);

                $oArticle = null;
                $rs->moveNext();
                $this->addItem($aItem);
            }
        }
    }

    /**
     * executes given functions, to fulfill $aItem.
     *
     * @param array $aLoaders method names in this class
     * @return array changed $aItem
     */
    protected function _executeLoaders(array $aLoaders)
    {
        $aArguments = func_get_args();
        array_shift($aArguments);
        foreach ($aLoaders as $sMethod) {
            if (method_exists($this, $sMethod)) {
                $aArguments[0] = call_user_func_array( array( $this, $sMethod ), $aArguments );
            }
        }
        return $aArguments[0];
    }

    /**
     * returns main loaders for article export
     * @see self::$_aMainItemLoaders
     * @return array
     */
    protected function _getMainItemLoaders()
    {
        return $this->_aMainItemLoaders;
    }

    /**
     * returns required field loaders for article export
     * @see self::$_aRequiredItemFieldLoaders
     * @return array
     */
    protected function _getRequiredItemFieldLoaders()
    {
        return $this->_aRequiredItemFieldLoaders;
    }

    /**
     * formats given price to shopgate standard.
     * @param double $dPrice
     * @return double
     */
    protected function _formatPrice($dPrice)
    {
        return round($dPrice, 2);
    }

    /**
     * loads is_available information for article.
     * Uses oxArticle::isBuyable() logic here
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_is_available(array $aItem, oxArticle $oArticle)
    {
        if ($oArticle->isBuyable()) {
            $aItem['is_available']  = 1;
        }
        else {
            $aItem['is_available']  = 0;
        }
        return $aItem;
    }

    /**
     * loads required fields from oxarticle object
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadRequiredFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        return $this->_executeLoaders($this->_getRequiredItemFieldLoaders(), $aItem, $oArticle);
    }

    /**
     * article number. used oxarticles__oxartnum
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_item_number(array $aItem, oxArticle $oArticle)
    {
        $aItem['item_number']   = $oArticle->oxarticles__oxartnum->value;
        return $aItem;
    }

    /**
     * article title.
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_item_name(array $aItem, oxArticle $oArticle)
    {
        $aItem['item_name']     = $oArticle->oxarticles__oxtitle->value;
        return $aItem;
    }

    /**
     * article price
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_unit_amount(array $aItem, oxArticle $oArticle)
    {
        $aItem['unit_amount']   = $this->_formatPrice($oArticle->getPrice()->getBruttoPrice());
        return $aItem;
    }

    /**
     * article long description
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_description(array $aItem, $oArticle)
    {
        if (method_exists($oArticle, 'getLongDesc')) {
            $aItem['description'] = $oArticle->getLongDesc();
        }
        else {
            $aItem['description'] = $this->_getArticleLongDesc($oArticle);
        }
        return $aItem;
    }

    /**
     * returns parsed through smarty article long description.
     * Used only for older then 4.5.0 oxid versions.
     * same as oxArticle::getLongDesc()
     * @param oxArticle $oArticle
     * @return string
     * @deprecated
     */
    protected function _getArticleLongDesc($oArticle)
    {
        $oLongDesc = $oArticle->getArticleLongDesc();
        if (null !== $oLongDesc->rawValue) {
            $sLongDesc = $oLongDesc->rawValue;
        }
        else {
            $sLongDesc = $oLongDesc->value;
        }
        $sLongDesc = oxUtilsView::getInstance()->parseThroughSmarty( $sLongDesc, $oArticle->getId().$oArticle->getLanguage() );
        return $sLongDesc;
    }

    /**
     * article main link
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_url_deeplink(array $aItem, oxArticle $oArticle)
    {
        $aItem['url_deeplink']  = $oArticle->getLink();
        return $aItem;
    }

    /**
     * loads article images. First tries to get Zoom images,
     * if no such images, default image will be taken
     *
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_url_images(array $aItem, oxArticle $oArticle)
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
        $aItem['urls_images']   = implode(self::MULTI_SEPERATOR, $aPictures);
        return $aItem;
    }

    /**
     * Loads categories to which article belongs
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_categories(array $aItem, oxArticle $oArticle)
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

    /**
     * returns associated array of categories path
     * array(category id => category path)
     * @param bool $blReset reload list again from DB
     * @return array
     */
    protected function _getCategoriesPath($blReset = false)
    {
        if ($this->_aCategoriesPath !== null && !$blReset) {
            return $this->_aCategoriesPath;
        }
        $this->_aCategoriesPath = array();

        foreach($this->_getCategoriesPathFromDB() as $aRes) {

            $sPath = '';
            if ($aRes['OXPARENTID'] != 'oxrootid') {
                $sPath = $this->_aCategoriesPath[$aRes['OXPARENTID']] . self::PARENT_SEPERATOR;
            }
            $this->_aCategoriesPath[$aRes['OXID']] = $sPath . $aRes['OXTITLE'];
        }
        return $this->_aCategoriesPath;
    }

    /**
     * Formats SQL, and returns results from DB in format:
     * array(array(OXID=>oxid, OXTITLE=>catname, OXPARENTID=>oxid), array(..)..)
     * @return array
     */
    protected function _getCategoriesPathFromDB()
    {
        /** @var $oCategory oxCategory */
        $oCategory = oxNew('oxCategory');
        $sCategoriesTable = $oCategory->getViewName();
        $sLangTag = $this->_getLanguageTagForTable($sCategoriesTable);
        $sTitleField = 'OXTITLE'.$sLangTag;
        $sSQL = " SELECT  OXID, {$sTitleField} as OXTITLE, OXPARENTID FROM {$sCategoriesTable} ORDER BY OXLEFT ";
        return $this->_dbGetAll($sSQL);
    }

    /**
     * db wrapper. Passes variables to mysql_driver_ADOConnection::GetAll().
     * if result != true, returns empty array
     * @param $sSQL
     * @param array $aParams
     * @return array
     */
    protected function _dbGetAll($sSQL, $aParams = array())
    {
        $aSqlRes = oxDb::getDb(true)->getAll($sSQL, $aParams);
        if (!$aSqlRes) {
            $aSqlRes = array();
        }
        return $aSqlRes;
    }

    /**
     * db wrapper. Passes variables to mysql_driver_ADOConnection::GetOne().
     * if result != true, returns empty array
     * @param $sSQL
     * @param array $aParams
     * @return string|null
     */
    protected function _dbGetOne($sSQL, $aParams = array())
    {
        $aSqlRes = oxDb::getDb()->GetOne($sSQL, $aParams);
        if (!$aSqlRes) {
            $aSqlRes = null;
        }
        return $aSqlRes;
    }


    /**
     * Loads manufacturer name to which article belongs
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_manufacturer(array $aItem, oxArticle $oArticle)
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

    /**
     * returns  associated array with manufacturers id and names
     * array(OXID=>oxtitle, OXID=>oxtitle)
     * @param bool $blReset reload from DB
     * @return array
     */
    protected function _getManufacturers($blReset = false)
    {
        if ($this->_aManufacturers !== null && !$blReset) {
            return $this->_aManufacturers;
        }
        $sManufacturersTable = getViewName('oxmanufacturers');
        $sLangTag = $this->_getLanguageTagForTable($sManufacturersTable);
        $sTitleField = 'OXTITLE'.$sLangTag;
        $sSQL = " SELECT OXID, {$sTitleField} as OXTITLE FROM {$sManufacturersTable} ";
        $this->_aManufacturers = $this->_dbGetAll($sSQL);
        return $this->_aManufacturers;
    }

    /**
     * Loads delivery time in text format for article
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_available_text(array $aItem, oxArticle $oArticle)
    {
        $sMinTime = 0;
        $sMaxTime = 0;
        $sTranslateIdent = '';
        if (isset($oArticle->oxarticles__oxmindeltime)) {
            $sMinTime = $oArticle->oxarticles__oxmindeltime->value;
        }
        if (isset($oArticle->oxarticles__oxmaxdeltime)) {
            $sMaxTime = $oArticle->oxarticles__oxmaxdeltime->value;
        }
        if (isset($oArticle->oxarticles__oxdeltimeunit)) {
            $sTranslateIdent = $oArticle->oxarticles__oxdeltimeunit->value;
        }
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
        if (!$sText) {
            $sText = $oArticle->oxarticles__oxstocktext->value;
        }
        else {
            $sText .= ' ';
            $sText .= $this->_getTranslation( $sTranslateIdent,
                                              array(
                                                   'PAGE_DETAILS_DELIVERYTIME_'.$sTranslateIdent,
                                                   'DETAILS_'.$sTranslateIdent
                                              )
            );
        }

        $aItem['available_text'] = $sText;
        return $aItem;
    }

    /**
     * returns required field loaders for article export
     * @see self::$_aAdditionalItemFieldLoaders
     * @return array
     */
    protected function _getAdditionalItemFieldLoaders()
    {
        return $this->_aAdditionalItemFieldLoaders;
    }

    /**
     * loads required fields from oxarticle object
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadAdditionalFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        return $this->_executeLoaders($this->_getAdditionalItemFieldLoaders(), $aItem, $oArticle);
    }


    /**
     * article number by manufacturer
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_manufacturer_item_number(array $aItem, oxArticle $oArticle)
    {
        $aItem['manufacturer_item_number']  = $oArticle->oxarticles__oxmpn->value;
        return $aItem;
    }

    /**
     * article currency
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_currency(array $aItem, oxArticle $oArticle)
    {
        $aItem['currency']  = $this->_getActiveCurrency();
        return $aItem;
    }

    /**
     * article VAT percentage
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_tax_percent(array $aItem, oxArticle $oArticle)
    {
        $aItem['tax_percent']  = $oArticle->getArticleVat();
        return $aItem;
    }

    /**
     * article recommended retail price (RRP)
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_msrp(array $aItem, oxArticle $oArticle)
    {
        $aItem['msrp']  = $this->_formatPrice($oArticle->getTPrice()->getBruttoPrice());
        return $aItem;
    }

    /**
     * article base price (price by one unit. by example 5EUR/liter )
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_basic_price(array $aItem, oxArticle $oArticle)
    {
        if ((double) $oArticle->oxarticles__oxunitquantity->value && $oArticle->oxarticles__oxunitname->value) {
            $aItem['basic_price'] = $this->_formatPrice($oArticle->getPrice()->getBruttoPrice() / (double) $oArticle->oxarticles__oxunitquantity->value);
        }
        else {
            $aItem['basic_price'] = '';
        }
        return $aItem;
    }

    /**
     * use or not stock control for this article
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_use_stock(array $aItem, oxArticle $oArticle)
    {
        if ($this->_blOxidUsesStock && $oArticle->oxarticles__oxstockflag->value != 4) {
            $aItem['use_stock'] = 1;
        }
        else {
            $aItem['use_stock'] = 0;
        }
        return $aItem;
    }

    /**
     * article available stock
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_stock_quantity(array $aItem, oxArticle $oArticle)
    {
        $aItem['stock_quantity']  = $oArticle->oxarticles__oxstock->value;
        return $aItem;
    }

    /**
     * article EAN number
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_ean(array $aItem, oxArticle $oArticle)
    {
        $aItem['ean']  = $oArticle->oxarticles__oxean->value;
        return $aItem;
    }

    /**
     * article last updated date
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_last_update(array $aItem, oxArticle $oArticle)
    {
        $iTime = strtotime($oArticle->oxarticles__oxtimestamp->value);
        if ($iTime) {
            $aItem['last_update']  = date('Y-m-d', $iTime);
        }
        else {
            $aItem['last_update'] = '';
        }
        return $aItem;
    }

    /**
     * article tags
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_tags(array $aItem, oxArticle $oArticle)
    {
        $aItem['tags']  = $oArticle->getTags();
        return $aItem;
    }

    /**
     * article marketplace filled in oxarticles__marm_shopgate_marketplace
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_marketplace(array $aItem, oxArticle $oArticle)
    {
        $aItem['marketplace'] = $oArticle->oxarticles__marm_shopgate_marketplace->value;
        return $aItem;
    }

    /**
     * article weight in grams
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_weight(array $aItem, oxArticle $oArticle)
    {
        $aItem['weight']  = $oArticle->oxarticles__oxweight->value*1000;
        return $aItem;
    }

    /**
     * article free shoping flag by oxarticles__oxfreeshipping
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_is_free_shipping(array $aItem, oxArticle $oArticle)
    {
        $aItem['is_free_shipping']  = $oArticle->oxarticles__oxfreeshipping->value;
        return $aItem;
    }

    /**
     * loads article amount prices
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_block_pricing(array $aItem, oxArticle $oArticle)
    {
        $aPriceInfo = $oArticle->loadAmountPriceInfo();
        $aParsed = array();
        foreach ($aPriceInfo as $oPriceItem) {
            $aParsed[] = $oPriceItem->oxprice2article__oxamount->value . self::PARENT_SEPERATOR . $oPriceItem->fbrutprice;
        }
        $aItem['block_pricing'] = implode(self::MULTI_SEPERATOR, $aParsed);
        return $aItem;
    }

    /**
     * loads article attributes
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_properties(array $aItem, oxArticle $oArticle)
    {
        $aProcessedAttributes = array();
        $aAttributes = $oArticle->getAttributes();
        foreach ($aAttributes as $oAttribute) {
            $aProcessedAttributes[] = $oAttribute->oxattribute__oxtitle->value
                                      . self::PARENT_SEPERATOR
                                      . $oAttribute->oxattribute__oxvalue->value;
        }
        $aItem['properties'] = implode(self::MULTI_SEPERATOR, $aProcessedAttributes);
        return $aItem;
    }

    /**
     * loads article selection list
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadSelectionListForArticle(array $aItem, $oArticle)
    {
        if (!method_exists($oArticle, 'getSelections')) {
            return $this->_loadSelectionListForArticle_for_older_then_450($aItem, $oArticle);
        }
        $oSelectionList = $oArticle->getSelections();
        if (!$oSelectionList) {
            $aItem['has_options'] = 0;
            return $aItem;
        }
        $aItem['has_options']   = 1;
        $iCount = 0;
        foreach ($oSelectionList as $oListItem) {
            $aSelections = $oListItem->getSelections();
            if ($aSelections) {
                $aSelectionValues = array();
                $iCount = $iCount + 1;
                $aItem['option_'.$iCount] = $oListItem->getLabel();
                $aSelectionValues = array();
                foreach ($aSelections as $oSelection) {
                    // TODO: price info in field field
                    $aSelectionValues[] = $oSelection->getName();
                }
                $aItem['option_'.$iCount.'_values'] = implode(self::MULTI_SEPERATOR, $aSelectionValues);
            }
        }
        
        return $aItem;
    }

    /**
     * loads article selection list.
     * Used only for older oxid version (before 4.5.0)
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     * @deprecated
     */
    protected function _loadSelectionListForArticle_for_older_then_450(array $aItem, $oArticle)
    {
        $aSelectionLists = $oArticle->getSelectLists();
        if (!$aSelectionLists) {
            $aItem['has_options'] = 0;
            return $aItem;
        }
        $aItem['has_options']   = 1;
        $iCount = 0;
        foreach ($aSelectionLists as $aSelectionList) {
            $iCount = $iCount + 1;
            $aItem['option_'.$iCount] = $aSelectionList['name'];
            $aSelectionValues = array();
            foreach ($aSelectionList as $oListItem) {
                if (is_object($oListItem) && isset($oListItem->name)) {
                   $aSelectionValues[] = $oListItem->name;
                }
            }
            $aItem['option_'.$iCount.'_values'] = implode(self::MULTI_SEPERATOR, $aSelectionValues);
        }
        return $aItem;
    }

    /**
     * loads variants info
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadVariantsInfoForArticle(array $aItem, oxArticle $oArticle)
    {
        return $this->_executeLoaders($this->_getVariantFieldLoaders(), $aItem, $oArticle);
    }

    /**
     * returns variant field loaders for article export
     * @see self::$_aVariantFieldLoaders
     * @return array
     */
    protected function _getVariantFieldLoaders()
    {
        return $this->_aVariantFieldLoaders;
    }

    /**
     * load boolean if article has variants.
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_has_children(array $aItem, oxArticle $oArticle)
    {
        if ($oArticle->oxarticles__oxvarcount->value) {
            $aItem['has_children']   = 1;
        }
        else {
            $aItem['has_children'] = 0;
        }
        return $aItem;
    }

    /**
     * if article has parent, loads it artnum
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_parent_item_number(array $aItem, oxArticle $oArticle)
    {
        if ($oParentArticle = $oArticle->getParentArticle()) {
            $aItem['parent_item_number'] = $oParentArticle->oxarticles__oxartnum->value;
        }
        else {
            $aItem['parent_item_number'] = '';
        }
        return $aItem;
    }

    /**
     * loads article variant name or selection name if parent
     * Works with multi-variants
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadArticleExport_attribute(array $aItem, oxArticle $oArticle)
    {
        $sVariantOptions = $oArticle->oxarticles__oxvarname->value;
        if ($oArticle->oxarticles__oxvarselect->value) {
            $sVariantOptions = $oArticle->oxarticles__oxvarselect->value;
        }
        if (!$sVariantOptions) {
            return $aItem;
        }
        $iCount = 0;
        foreach (explode($this->_sVariantSeparator, $sVariantOptions) as $sVariantOption) {
            $iCount = $iCount + 1;
            $aItem['attribute_'.$iCount] = $sVariantOption;
        }
        return $aItem;
    }

    /**
     * persistent param adding, if  oxarticles__oxisconfigurable is set
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _loadPersParamForArticle(array $aItem, oxArticle $oArticle)
    {
        if ($oArticle->oxarticles__oxisconfigurable->value) {
            $aItem['has_input_fields']      = 1;
            $aItem['input_field_1_type']    = 'text';
            $aItem['input_field_1_label']   = $this->_getTranslation('LABEL', array('PAGE_DETAILS_PERSPARAM_LABEL', 'DETAILS_LABEL'));
            $aItem['input_field_1_required']= 1;
        }
        else {
            $aItem['has_input_fields'] = 0;
        }
        return $aItem;
    }

    /**
     * translates given ident, or picks firs translation from alternatives
     * @param string $sIdent
     * @param array $aAlternatives
     * @return string
     */
    protected function _getTranslation($sIdent, $aAlternatives = array())
    {
        $oLang = oxLang::getInstance();
        $sTranslation = $oLang->translateString($sIdent);
        if (count($aAlternatives) && $sTranslation == $sIdent) {
            foreach ($aAlternatives as $sAlternative) {
                $sTranslation = $oLang->translateString($sAlternative);
                if ($sAlternative != $sTranslation) {
                    return $sTranslation;
                }
            }
        }
        return $sTranslation;
    }

    /**
     * function to get language tag in oxid versions 4.0 - 4.5 formats
     * in 4.5 introduced new views for each language with language abbr in ending.
     * example: oxv_oxarticles_en, oxv_oxcategories_de
     * @param $sTableName
     * @return string
     */
    protected function _getLanguageTagForTable($sTableName)
    {
        $oLang = oxLang::getInstance();
        $sLangTag  = $oLang->getLanguageTag();
        $sLangAbbr = $oLang->getLanguageAbbr();
        if (strpos($sTableName, 'oxv_') !== false && strpos($sTableName, '_'.$sLangAbbr) !== false) {
            $sLangTag = '';
        }
        return $sLangTag;
    }

    /**
     * NOT IMPLEMENTED YET
     * @return void
     */
    protected function createCategoriesCsv()
    {

    }

    /**
     * NOT IMPLEMENTED YET
     * @return void
     */
    protected function createReviewsCsv()
    {

    }
    
    /**
     * NOT IMPLEMENTED YET
     * @return void
     */
    protected function createPagesCsv()
    {

    }

    /**
     * creates user objects and returns it
     * @return ShopgateShopCustomer
     */
    protected function _createShopCustomerObject()
    {
        return new ShopgateShopCustomer();
    }

    /**
     * Responsible for gettin user data from shop to shopgate
     * @throws ShopgateConnectException
     * @param string $user
     * @param string $pass
     * @return ShopgateShopCustomer
     */
    public function getUserData($user, $pass)
    {
        $oUser = oxNew( 'oxuser' );
        try{
            $oUser->login( $user, $pass);
        }
        catch(Exception $e){
            throw new ShopgateConnectException("Invalid username or password", ShopgateConnectException::INVALID_USERNAME_OR_PASSWORD);
        }

        $oUserData = $this->_createShopCustomerObject();
        $oUserData->setCustomerNumber($oUser->oxuser__oxcustnr->value);
        $oUserData->setFirstName($oUser->oxuser__oxfname->value);
        $oUserData->setSurname($oUser->oxuser__oxlname->value);
        $oUserData->setMail($oUser->oxuser__oxusername->value);
        $oUserData->setPhone($oUser->oxuser__oxfon->value);
        $oUserData->setMobile($oUser->oxuser__oxmobfon->value);
        if (strtolower($oUser->oxuser__oxsal->value) == 'mrs') {
            $oUserData->setGender(ShopgateShopCustomer::FEMALE);
        }
        elseif (strtolower($oUser->oxuser__oxsal->value) == 'mr')  {
            $oUserData->setGender(ShopgateShopCustomer::MALE);
        }
        $oUserData->setStreet($oUser->oxuser__oxstreet->value . ' ' . $oUser->oxuser__oxstreetnr->value);
        $oUserData->setCity($oUser->oxuser__oxcity->value);
        $oUserData->setZip($oUser->oxuser__oxzip->value);
        $oUserData->setCountry($oUser->getUserCountry());
        if ($oUser->oxuser__oxcompany->value) {
            $oUserData->setCompany($oUser->oxuser__oxcompany->value);
        }
        else {
            $oUserData->setCompany('NONE');
        }
        return $oUserData;
    }
    
    /**
     * NOT IMPLEMENTED YET
     * @return void
     */
    public function createShopInfo()
    {

    }


    /**
     * returns main loaders for order import
     * @see self::$_aOrderImportLoaders
     * @return array
     */
    protected function _getOrderImportLoaders()
    {
        return $this->_aOrderImportLoaders;
    }

    /**
     * this function will import Shopgate
     * @param ShopgateOrder $order
     * @return void
     */
    public function saveOrder(ShopgateOrder $order)
    {
        $this->_checkOrder($order);

        $oNewOrder = oxNew('oxorder');

        $oNewOrder = $this->_executeLoaders($this->_getOrderImportLoaders(), $oNewOrder, $order);

        $oNewOrder->save();

        // copies basket product info ...
        $this->_saveOrderArticles($oNewOrder, $order);
    }

    /**
     * returns updated order with order sums, vats and currency
     * @param oxOrder $oOxidOrder this order will be updated
     * @param ShopgateOrder $oShopgateOrder from here info will be taken
     * @return oxOrder updated order
     */
    protected function _loadOrderPrice(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        $oOxidOrder->oxorder__oxtotalordersum = new oxField($oShopgateOrder->getAmountComplete()/100, oxField::T_RAW);
        /** @var $oPrice oxPrice */
        $oPrice = oxNew('oxprice');
        $oPrice->setPrice($oShopgateOrder->getAmountItems()/100.0, oxConfig::getInstance()->getConfigParam( 'dDefaultVAT' ));

        $oOxidOrder->oxorder__oxtotalnetsum   = new oxField(oxUtils::getInstance()->fRound($oPrice->getNettoPrice()), oxField::T_RAW);
        $oOxidOrder->oxorder__oxtotalbrutsum  = new oxField(oxUtils::getInstance()->fRound($oPrice->getBruttoPrice()), oxField::T_RAW);

        $dVatSum = $oOxidOrder->oxorder__oxtotalbrutsum->value - $oOxidOrder->oxorder__oxtotalnetsum->value;
        $oOxidOrder->oxorder__oxartvat1      = new oxField($oPrice->getVat(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxartvatprice1 = new oxField($dVatSum, oxField::T_RAW);

        // currency
        $oOxidOrder->oxorder__oxcurrency = new oxField($oShopgateOrder->getOrderCurrency());
        $oOxidOrder->oxorder__oxcurrate  = new oxField(1, oxField::T_RAW);

        return $oOxidOrder;
    }

    /**
     * returns updated order with user delivery notes
     * @param oxOrder $oOxidOrder this order will be updated
     * @param ShopgateOrder $oShopgateOrder from here info will be taken
     * @return oxOrder updated order
     */
    protected function _loadOrderRemark(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        // user remark
        $aDeliveryNotes = $oShopgateOrder->getDeliversNotes();
        if ( $aDeliveryNotes ) {
            if (is_array($aDeliveryNotes)) {
                $oOxidOrder->oxorder__oxremark = new oxField(var_export($aDeliveryNotes, true), oxField::T_RAW);
            }
            else {
                $oOxidOrder->oxorder__oxremark = new oxField($aDeliveryNotes, oxField::T_RAW);
            }

        }

        return $oOxidOrder;
    }

    /**
     * returns updated order with user delivery notes
     * @param oxOrder $oOxidOrder this order will be updated
     * @param ShopgateOrder $oShopgateOrder from here info will be taken
     * @return oxOrder updated order
     */
    protected function _loadOrderAdditionalInfo(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        //order language
        $oOxidOrder->oxorder__oxlang = new oxField( $oOxidOrder->getOrderLanguage() );

        $oOxidOrder->oxorder__oxtransstatus = new oxField('FROM_SHOPGATE', oxField::T_RAW);
        $oOxidOrder->oxorder__oxfolder      = new oxField(key( oxConfig::getInstance()->getConfigParam(  'aOrderfolder' ) ), oxField::T_RAW);
        $oOxidOrder->oxorder__marm_shopgate_order_number = new oxField($oShopgateOrder->getOrderNumber(), oxField::T_RAW);

        return $oOxidOrder;
    }

    /**
     * returns updated order with customer billing and shipping address
     * @param oxOrder $oOxidOrder this order will be updated
     * @param ShopgateOrder $oShopgateOrder from here info will be taken
     * @return oxOrder updated order
     */
    protected function _loadOrderContacts(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        $sOrderEmail = $oShopgateOrder->getCustomerMail();
        $oOxidOrder->oxorder__oxuserid       = new oxField($this->_getUserOxidByEmail($sOrderEmail), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillemail       = new oxField($sOrderEmail, oxField::T_RAW);

        $oInvoiceAddress = $oShopgateOrder->getInvoiceAddress();
        $oOxidOrder = $this->_loadOrderAddress( $oOxidOrder,
            $oShopgateOrder,
            'oxbill',
            $oInvoiceAddress
        );

        $oDelAddress = $oShopgateOrder->getDeliveryAddress();
        $oOxidOrder = $this->_loadOrderAddress( $oOxidOrder,
            $oShopgateOrder,
            'oxdel',
            $oDelAddress
        );

        return $oOxidOrder;
    }

    /**
     * load contact details from shopgate order address to oxid
     * @param oxOrder $oOxidOrder where to store data
     * @param ShopgateOrder $oShopgateOrder main order object
     * @param string $sTarget oxbill or oxdel
     * @param ShopgateOrderAddress $oShopgateOrderAddress address fields taken from
     * @return oxOrder changed oxorder
     */
    protected function _loadOrderAddress(
        oxOrder $oOxidOrder,
        ShopgateOrder $oShopgateOrder,
        $sTarget,
        ShopgateOrderAddress $oShopgateOrderAddress
    )
    {
        $sPhone = (string) $oShopgateOrder->getCustomerMobile();
        if (empty($sPhone)) {
            $sPhone = (string) $oShopgateOrder->getCustomerPhone();
        }
        $oOxidOrder->{"oxorder__{$sTarget}company"}     = new oxField($oShopgateOrderAddress->getCompany(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}fname"}       = new oxField($oShopgateOrderAddress->getFirstName(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}lname"}       = new oxField($oShopgateOrderAddress->getSurname(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}street"}      = new oxField($oShopgateOrderAddress->getStreet(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}city"}        = new oxField($oShopgateOrderAddress->getCity(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}countryid"}   = new oxField(
            $this->_getCountryId($oShopgateOrderAddress->getCountry(), $oShopgateOrderAddress->getCountryName()),
            oxField::T_RAW
        );
        $oOxidOrder->{"oxorder__{$sTarget}stateid"}   = new oxField(
            $this->_getStateId($oShopgateOrderAddress->getState(), $oShopgateOrderAddress->getStateName()),
            oxField::T_RAW
        );
        $oOxidOrder->{"oxorder__{$sTarget}zip"}         = new oxField($oShopgateOrderAddress->getZipcode(), oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}fon"}         = new oxField($sPhone, oxField::T_RAW);
        $oOxidOrder->{"oxorder__{$sTarget}fax"}         = new oxField($oShopgateOrder->getCustomerFax(), oxField::T_RAW);
        if (strtolower($oShopgateOrderAddress->getGender()) == 'm') {
        $oOxidOrder->{"oxorder__{$sTarget}sal"}         = new oxField('MR', oxField::T_RAW);
        }
        elseif (strtolower($oShopgateOrderAddress ->getGender()) == 'f') {
            $oOxidOrder->{"oxorder__{$sTarget}sal"}         = new oxField('MRS', oxField::T_RAW);
        }

        return $oOxidOrder;
    }

    /**
     * returns user oxid if it exist by email
     * @param $sUserEmail
     * @return null|string
     */
    protected function _getUserOxidByEmail($sUserEmail)
    {
        $sUserOxid = $this->_dbGetOne( "SELECT OXID FROM oxuser WHERE OXUSERNAME = ?",
            array($sUserEmail)
        );
        return $sUserOxid;
    }

    /**
     * searches for country oxid by its ISO number or title
     * @param $sShopgateCountryId
     * @param $sCountryName
     * @return null|string
     */
    protected function _getCountryId($sShopgateCountryId, $sCountryName)
    {
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sCountryId = $this->_dbGetOne( "SELECT OXID FROM oxcountry WHERE OXISOALPHA2 = ? OR OXTITLE{$sLangTag} = ?",
            array($sShopgateCountryId, $sCountryName)
        );
        return $sCountryId;
    }

    /**
     * searches for state oxid by its ISO number or title
     * @param $sShopgateStateId
     * @param $sStateName
     * @return null|string
     */
    protected function _getStateId($sShopgateStateId, $sStateName)
    {
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sCountryId = $this->_dbGetOne( "SELECT OXID FROM oxstates WHERE OXISOALPHA2 = ? OR OXTITLE{$sLangTag} = ?",
            array($sShopgateStateId, $sStateName)
        );
        return $sCountryId;
    }

    protected function _saveOrderArticles(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        $oArticles = oxNew( 'oxlist' );
        foreach ($oShopgateOrder->getOrderItems() as $oShopgateOrderItem) {
            /**@var $oShopgateOrderItem ShopgateOrderItem */

            $oProduct = $this->_getArticleByNumber($oShopgateOrderItem->getItemNumber());

            $oOrderArticle = oxNew( 'oxorderarticle' );

            $oOrderArticle->setIsNewOrderItem( true );
            $oOrderArticle->copyThis( $oProduct );
            $oOrderArticle->setId();

            $oOrderArticle = $this->_loadSelectionsForOrderArticle($oOrderArticle, $oShopgateOrderItem, $oProduct);

            $oOrderArticle->oxorderarticles__oxartnum     = new oxField( $oShopgateOrderItem->getItemNumber(), oxField::T_RAW );
            $oOrderArticle->oxorderarticles__oxtitle      = new oxField( $oShopgateOrderItem->getName(), oxField::T_RAW );
//            $oOrderArticle->oxorderarticles__oxpersparam = new oxField( serialize( array() ), oxField::T_RAW );

            // ids, titles, numbers ...
            $oOrderArticle->oxorderarticles__oxorderid = new oxField( $oOxidOrder->getId() );
            $oOrderArticle->oxorderarticles__oxartid   = new oxField( $oProduct->getId() );
            $oOrderArticle->oxorderarticles__oxamount  = new oxField( $oShopgateOrderItem->getQuantity() );

            // prices
            $oOrderArticle->oxorderarticles__oxnetprice  = new oxField( $oShopgateOrderItem->getUnitAmount()/100.0, oxField::T_RAW );
            $oOrderArticle->oxorderarticles__oxvatprice  = new oxField( ($oShopgateOrderItem->getUnitAmountWithTax() - $oShopgateOrderItem->getUnitAmount())/100.0, oxField::T_RAW );
            $oOrderArticle->oxorderarticles__oxbrutprice = new oxField( $oShopgateOrderItem->getUnitAmountWithTax()/100.0, oxField::T_RAW );
            $oOrderArticle->oxorderarticles__oxvat       = new oxField( $oShopgateOrderItem->getTaxPercent(), oxField::T_RAW );

            $oOrderArticle->oxorderarticles__oxnprice = new oxField( $oShopgateOrderItem->getUnitAmount()/$oShopgateOrderItem->getQuantity()/100.0, oxField::T_RAW );
            $oOrderArticle->oxorderarticles__oxbprice = new oxField( $oShopgateOrderItem->getUnitAmountWithTax()/$oShopgateOrderItem->getQuantity()/100.0, oxField::T_RAW );

            // items shop id
            $oOrderArticle->oxorderarticles__oxordershopid = new oxField( $oProduct->getShopId(), oxField::T_RAW );

            $oArticles->offsetSet( $oOrderArticle->getId(), $oOrderArticle );
        }
        $oOxidOrder->setOrderArticleList($oArticles);
        $oOxidOrder->save();

    }

    /**
     * loads selection names and values to oxorderarticles__oxselvariant
     * @param oxorderarticle $oOrderArticle
     * @param ShopgateOrderItem $oShopgateOrderItem
     * @param oxArticle $oProduct
     * @return oxorderarticle
     */
    protected function _loadSelectionsForOrderArticle(
            oxorderarticle $oOrderArticle,
            ShopgateOrderItem $oShopgateOrderItem,
            oxArticle $oProduct
        )
    {
        // set chosen selectlist
        $sSelList = '';
        if ( $oShopgateOrderItem->getHasOptions() ) {
            foreach ( $oShopgateOrderItem->getOptions() as $oOption ) {
                if ( $sSelList ) {
                    $sSelList .= ", ";
                }
                $sSelList .= "{$oOption->getName()} : {$oOption->getValue()}";
            }
        }
        $oOrderArticle->oxorderarticles__oxselvariant = new oxField( trim( $sSelList.' '.$oProduct->oxarticles__oxvarselect->value ), oxField::T_RAW );
        return $oOrderArticle;

    }

    /**
     * loads shop article by article number (at this moment oxartnum is used)
     * @param $sArticleNumber
     * @return oxarticle
     */
    protected function _getArticleByNumber($sArticleNumber)
    {
        $sArticleTable = getViewName('oxarticles');
        $sArticleId = $this->_dbGetOne("SELECT oxid FROM {$sArticleTable} WHERE oxartnum = ?", array($sArticleNumber));
        return oxNewArticle($sArticleId);
    }

    /**
     * returns active currency name
     * example: EUR
     * @param bool $blReset
     * @return string
     */
    protected function _getActiveCurrency($blReset = false)
    {
        if ($this->_sCurrency !== null && !$blReset) {
            return $this->_sCurrency;
        }
        $oCur = oxConfig::getInstance()->getActShopCurrencyObject();
        $this->_sCurrency = $oCur->name;
        return $this->_sCurrency;

    }

    /**
     * if given order exists in oxid, throws exception.
     * @throws ShopgateFrameworkException
     * @param ShopgateOrder $oShopgateOrder
     * @return void
     */
    protected function _checkOrder( ShopgateOrder $oShopgateOrder )
    {
        $sSavedOrder = $this->_dbGetOne( "SELECT OXORDERNR FROM oxorder WHERE marm_shopgate_order_number = ?",
            array($oShopgateOrder->getOrderNumber())
        );
        if ($sSavedOrder) {
            throw new ShopgateFrameworkException('Order already stored in oxid. Order number: '.$sSavedOrder);
        }
    }
}
