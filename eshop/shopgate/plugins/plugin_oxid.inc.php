<?php


class ShopgatePlugin extends ShopgatePluginCore {

    const MULTI_SEPERATOR = '||';
    const PARENT_SEPERATOR = '=>';

    protected $_sVariantSeparator = " | ";

    protected $_sCurrency = 'EUR';
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
     * loads variables that are used in runtime
     * @return bool true
     */
    public function startup()
    {
        $oCur = oxConfig::getInstance()->getActShopCurrencyObject();
        $this->_sCurrency = $oCur->name;
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
     * loads default row ($this->buildDefaultRow()) and
     * for each article will overwrite data, and pass to $this->addItem()
     * @return void
     */
    protected function createItemsCsv()
    {
        $oArticleBase = $this->_getArticleBase();

        $sSelect = $this->_getArticleSQL($oArticleBase);

        $aDefaultRow = $this->buildDefaultRow();
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
     * @param array $aItem where to fill information
     * @param oxArticle $oArticle from here info will be taken
     * @return array changed $aItem
     */
    protected function _executeLoaders(array $aLoaders, array $aItem, oxArticle $oArticle)
    {
        foreach ($aLoaders as $sMethod) {
            if (method_exists($this, $sMethod)) {
                $aItem = $this->{$sMethod}($aItem, $oArticle);
            }
        }
        return $aItem;
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
     * @param array $aItem
     * @param oxArticle $oArticle
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
    
    protected function _loadRequiredFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        $aItem['item_number']   = $oArticle->oxarticles__oxartnum->value;
        $aItem['item_name']     = $oArticle->oxarticles__oxtitle->value;
        $aItem['unit_amount']   = $this->_formatPrice($oArticle->getPrice()->getBruttoPrice());
        $aItem['description']   = $oArticle->getLongDesc();

        $aItem = $this->_loadArticleExport_url_images($aItem, $oArticle);
        $aItem = $this->_loadArticleExport_categories($aItem, $oArticle);
        $aItem = $this->_loadArticleExport_is_available($aItem, $oArticle);
        $aItem = $this->_loadArticleExport_available_text($aItem, $oArticle);
        $aItem = $this->_loadArticleExport_manufacturer($aItem, $oArticle);
        $aItem['url_deeplink']  = $oArticle->getLink();

        return $aItem;
    }

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
        $aItem['urls_images']   = implode('||', $aPictures);
        return $aItem;
    }


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

    protected $_aCategoriesPath = null;
    protected function _getCategoriesPath($blReset = false)
    {
        if ($this->_aCategoriesPath !== null && !$blReset) {
            return $this->_aCategoriesPath;
        }
        $sCategoriesTable = getViewName('oxcategories');
        $sLangTag = $this->_getLanguageTagForTable($sCategoriesTable);
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

    protected $_aManufacturers = null;
    protected function _getManufacturers($blReset = false)
    {
        if ($this->_aManufacturers !== null && !$blReset) {
            return $this->_aManufacturers;
        }
        $sManufacturersTable = getViewName('oxmanufacturers');
        $sLangTag = $this->_getLanguageTagForTable($sManufacturersTable);
        $sTitleField = 'OXTITLE'.$sLangTag;
        $sSQL = "
            SELECT
              OXID,
              {$sTitleField} as OXTITLE
            FROM
              {$sManufacturersTable}
        ";
        $this->_aManufacturers = oxDb::getDb(true)->GetAssoc($sSQL);
        if (!$this->_aManufacturers) {
            $this->_aManufacturers = array();
        }
        return $this->_aManufacturers;
    }

    protected function _loadArticleExport_available_text(array $aItem, oxArticle $oArticle)
    {
        $sMinTime = $oArticle->oxarticles__oxmindeltime->value;
        $sMaxTime = $oArticle->oxarticles__oxmaxdeltime->value;
        $sTranslateIdent = $oArticle->oxarticles__oxdeltimeunit->value;
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
        $sText .= ' '.$this->_getTranslation(
            $sTranslateIdent,
            array(
                 'PAGE_DETAILS_DELIVERYTIME_'.$sTranslateIdent,
                 'DETAILS_'.$sTranslateIdent
            )
        );

        $aItem['available_text'] = $sText;
        return $aItem;
    }

    protected function _loadAdditionalFieldsForArticle(array $aItem, oxArticle $oArticle)
    {
        $aItem = $this->_loadAttributesForArticle($aItem, $oArticle);
        $aItem['manufacturer_item_number'] = $oArticle->oxarticles__oxmpn->value;
        $aItem['currency'] = $this->_sCurrency;
        $aItem['tax_percent'] = $oArticle->getArticleVat();
        $aItem['msrp'] = $this->_formatPrice($oArticle->getTPrice()->getBruttoPrice());

//        $aItem['shipping_costs_per_order'] = $oArticle->oxarticles__ox->value;
//        $aItem['additional_shipping_costs_per_unit'] = $oArticle->oxarticles__ox->value;

        if ((double) $oArticle->oxarticles__oxunitquantity->value && $oArticle->oxarticles__oxunitname->value) {
            $aItem['basic_price'] = $this->_formatPrice($oArticle->getPrice()->getBruttoPrice() / (double) $oArticle->oxarticles__oxunitquantity->value);
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
        $aItem['properties'] = implode(self::MULTI_SEPERATOR, $aProcessedAttributes);
        return $aItem;
    }

    protected function _loadSelectionListForArticle(array $aItem, oxArticle $oArticle)
    {
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
                $iCount++;
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

    protected function _loadVariantsInfoForArticle(array $aItem, oxArticle $oArticle)
    {
        if ($oArticle->oxarticles__oxvarcount->value) {
            $aItem['has_children']   = 1;
        }
        else {
            $aItem['has_children'] = 0;
        }
        if ($oParentArticle = $oArticle->getParentArticle()) {
            $aItem['parent_item_number'] = $oParentArticle->oxarticles__oxartnum->value;
        }
        else {
            $aItem['parent_item_number'] = 0;
        }
        $sVariantOptions = $oArticle->oxarticles__oxvarname->value;
        if ($oArticle->oxarticles__oxvarselect->value) {
            $sVariantOptions = $oArticle->oxarticles__oxvarselect->value;
        }
        if (!$sVariantOptions) {
            return $aItem;
        }
        $iCount = 0;
        foreach (explode($this->_sVariantSeparator, $sVariantOptions) as $sVariantOption) {
            $iCount++;
            $aItem['attribute_'.$iCount] = $sVariantOption;
        }
        return $aItem;
    }

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

    protected function _getLanguageTagForTable($sTableName)
    {
        $oLang = oxLang::getInstance();
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sLangAbbr = $oLang->getLanguageAbbr();
        if (strpos($sTableName, 'oxv_') !== false && strpos($sTableName, '_'.$sLangAbbr) !== false) {
            $sLangTag = '';
        }
        return $sLangTag;
    }

    protected function createReviewsCsv()
    {

    }
    
    protected function createPagesCsv()
    {

    }
    
    public function getUserData($user, $pass)
    {
        $oUser = oxNew( 'oxuser' );
        try{
            $oUser->login( $user, $pass);
        }
        catch(Exception $e){
            throw new ShopgateConnectException("Invalid username or password", ShopgateConnectException::INVALID_USERNAME_OR_PASSWORD);
        }

        $oUserData = new ShopgateShopCustomer();
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
    
    public function createShopInfo(){}

    public function saveOrder(ShopgateOrder $order)
    {
        $oNewOrder = oxNew('oxorder');

        $oNewOrder->oxorder__oxtotalordersum = new oxField($order->getAmountComplete()/100, oxField::T_RAW);
        /** @var $oPrice oxPrice */
        $oPrice = oxNew('oxprice');
        $oPrice->setPrice($order->getAmountItems()/100.0, oxConfig::getInstance()->getConfigParam( 'dDefaultVAT' ));

        $oNewOrder->oxorder__oxtotalnetsum   = new oxField(oxUtils::getInstance()->fRound($oPrice->getNettoPrice()), oxField::T_RAW);
        $oNewOrder->oxorder__oxtotalbrutsum  = new oxField(oxUtils::getInstance()->fRound($oPrice->getBruttoPrice()), oxField::T_RAW);

        $oNewOrder->oxorder__oxartvat1      = new oxField($oPrice->getVat(), oxField::T_RAW);
        $oNewOrder->oxorder__oxartvatprice1 = new oxField($oPrice->getVatValue(), oxField::T_RAW);

        // user remark
        $aDeliveryNotes = $order->getDeliversNotes();
        if ( $aDeliveryNotes ) {
            if (is_array($aDeliveryNotes)) {
                $oNewOrder->oxorder__oxremark = new oxField(var_export($aDeliveryNotes, true), oxField::T_RAW);
            }
            else {
                $oNewOrder->oxorder__oxremark = new oxField($aDeliveryNotes, oxField::T_RAW);
            }

        }
        $oNewOrder = $this->_loadOrderContacts($oNewOrder, $order);

        // currency
        $oNewOrder->oxorder__oxcurrency = new oxField($order->getOrderCurrency());
        $oNewOrder->oxorder__oxcurrate  = new oxField(1, oxField::T_RAW);

        //order language
        $oNewOrder->oxorder__oxlang = new oxField( $oNewOrder->getOrderLanguage() );


        $oNewOrder->oxorder__oxtransstatus = new oxField('FROM_SHOPGATE', oxField::T_RAW);
        $oNewOrder->oxorder__oxfolder      = new oxField(key( oxConfig::getInstance()->getShopConfVar(  'aOrderfolder' ) ), oxField::T_RAW);

//        $oNewOrder->oxorder__oxpaymentid
//        $oNewOrder->oxorder__oxpaymenttype
//        $oNewOrder->oxorder__oxdeltype

        $oNewOrder->save();

        // copies basket product info ...
        $this->_saveOrderArticles($oNewOrder, $order);

        $oNewOrder->save();
    }

    protected function _loadOrderContacts(oxOrder $oOxidOrder, ShopgateOrder $oShopgateOrder)
    {
        $sPhone = $oShopgateOrder->getCustomerMobile();
        if (empty($sPhone)) {
            $sPhone = $oShopgateOrder->getCustomerPhone();
        }
        $oOxidOrder->oxorder__oxuserid       = new oxField($this->_getUserOxidByEmail($oShopgateOrder->getCustomerMail()), oxField::T_RAW);

        $oInvoiceAddress = $oShopgateOrder->getInvoiceAddress();
        // bill address
        $oOxidOrder->oxorder__oxbillcompany     = new oxField($oInvoiceAddress->getCompany(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillemail       = new oxField($oShopgateOrder->getCustomerMail(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillfname       = new oxField($oInvoiceAddress->getFirstName(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbilllname       = new oxField($oInvoiceAddress->getSurname(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillstreet      = new oxField($oInvoiceAddress->getStreet(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillcity        = new oxField($oInvoiceAddress->getCity(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillcountryid   = new oxField(
            $this->_getCountryId($oInvoiceAddress->getCountry(), $oInvoiceAddress->getCountryName()),
            oxField::T_RAW
        );
        $oOxidOrder->oxorder__oxbillstateid   = new oxField(
            $this->_getCountryId($oInvoiceAddress->getState(), $oInvoiceAddress->getStateName()),
            oxField::T_RAW
        );
        $oOxidOrder->oxorder__oxbillzip         = new oxField($oInvoiceAddress->getZipcode(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillfon         = new oxField($sPhone, oxField::T_RAW);
        $oOxidOrder->oxorder__oxbillfax         = new oxField($oShopgateOrder->getCustomerFax(), oxField::T_RAW);
        if (strtolower($oInvoiceAddress->getGender()) == 'm') {
        $oOxidOrder->oxorder__oxbillsal         = new oxField('MR', oxField::T_RAW);
        }
        elseif (strtolower($oInvoiceAddress->getGender()) == 'f') {
            $oOxidOrder->oxorder__oxbillsal         = new oxField('MRS', oxField::T_RAW);
        }

        $oDelAdress = $oShopgateOrder->getDeliveryAddress();

        $oOxidOrder->oxorder__oxdelcompany     = new oxField($oDelAdress->getCompany(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelfname       = new oxField($oDelAdress->getFirstName(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdellname       = new oxField($oDelAdress->getSurname(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelstreet      = new oxField($oDelAdress->getStreet(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelcity        = new oxField($oDelAdress->getCity(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelcountryid   = new oxField(
            $this->_getCountryId($oDelAdress->getCountry(), $oDelAdress->getCountryName()),
            oxField::T_RAW
        );
        $oOxidOrder->oxorder__oxdelstateid   = new oxField(
            $this->_getCountryId($oDelAdress->getState(), $oDelAdress->getStateName()),
            oxField::T_RAW
        );
        $oOxidOrder->oxorder__oxdelzip         = new oxField($oDelAdress->getZipcode(), oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelfon         = new oxField($sPhone, oxField::T_RAW);
        $oOxidOrder->oxorder__oxdelfax         = new oxField($oShopgateOrder->getCustomerFax(), oxField::T_RAW);
        if (strtolower($oDelAdress->getGender()) == 'm') {
        $oOxidOrder->oxorder__oxdelsal         = new oxField('MR', oxField::T_RAW);
        }
        elseif (strtolower($oInvoiceAddress->getGender()) == 'f') {
            $oOxidOrder->oxorder__oxdelsal         = new oxField('MRS', oxField::T_RAW);
        }

        return $oOxidOrder;
    }

    protected function _getUserOxidByEmail($sUserEmail)
    {
        $sUserOxid = oxDb::getDb()->getOne(
            "SELECT OXID FROM oxuser WHERE OXUSERNAME = ?",
            array($sUserEmail)
        );
        if (!$sUserOxid) {
            $sUserOxid = null;
        }
        return $sUserOxid;
    }

    protected function _getCountryId($sShopgateCountryId, $sCountryName)
    {
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sCountryId = oxDb::getDb()->getOne(
            "SELECT OXID FROM oxcountry WHERE OXISOALPHA2 = ? OR OXTITLE{$sLangTag} = ?",
            array($sShopgateCountryId, $sCountryName)
        );
        if (!$sCountryId) {
            $sCountryId = null;
        }
        return $sCountryId;
    }

    protected function _getStateId($sShopgateStateId, $sStateName)
    {
        $sLangTag = oxLang::getInstance()->getLanguageTag();
        $sCountryId = oxDb::getDb()->getOne(
            "SELECT OXID FROM oxstates WHERE OXISOALPHA2 = ? OR OXTITLE{$sLangTag} = ?",
            array($sShopgateStateId, $sStateName)
        );
        if (!$sCountryId) {
            $sCountryId = null;
        }
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
    }

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
        $oOrderArticle->oxorderarticles__oxselvariant = new oxField( trim( $sSelList.' '.$oProduct->oxarticles__oxvarselect->getRawValue() ), oxField::T_RAW );
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
        $sArticleId = oxDb::getDb()->getOne("SELECT oxid FROM {$sArticleTable} WHERE oxartnum = ?", array($sArticleNumber));
        return oxNewArticle($sArticleId);
    }
}
