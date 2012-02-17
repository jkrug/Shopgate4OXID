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

$sLangName  = "Deutsch";

$aLang = array(
    'charset'                                   				=> 'ISO-8859-15',
    'tbclmarm_shopgate_config'                  				=> 'Shopgate',
    'tbclmarm_shopgate_article'                 				=> 'Shopgate',
    'MARM_SHOPGATE_ARTICLE_MARKETPLACE'          				=> 'Produkt in Shopgate listen',
    'MARM_SHOPGATE_ARTICLE_MARKETPLACE_HELP'     				=> 'Den Artikel im Shopgate-Marktplatz anzeigen.',
    'MARM_SHOPGATE_CONFIG_GROUP_GENERAL'        				=> 'Grundeinstellungen',
    'MARM_SHOPGATE_CONFIG_SHOP_IS_ACTIVE'       				=> 'Ist Ihr Shop bei Shopgate frei geschaltet?',
    //'MARM_SHOPGATE_CONFIG_SHOP_IS_ACTIVE_HELP'  				=> 'Soll Ihr Shopgate-Shop aktiviert sein?',
    'MARM_SHOPGATE_CONFIG_CUSTOMER_NUMBER'      				=> 'Kundennummer',
    'MARM_SHOPGATE_CONFIG_CUSTOMER_NUMBER_HELP' 				=> 'Ihre Kundennummer bei Shopgate',
    'MARM_SHOPGATE_CONFIG_SHOP_NUMBER'          				=> 'Shopnummer',
    'MARM_SHOPGATE_CONFIG_SHOP_NUMBER_HELP'     				=> 'Ihre Shopnummer bei Shopgate',
    'MARM_SHOPGATE_CONFIG_APIKEY'               				=> 'API Key',
    'MARM_SHOPGATE_CONFIG_APIKEY_HELP'          				=> 'Ihr pers&ouml;nlicher API-Key. Sie finden diesen in Ihren H&auml;ndlereinstellungen unter Stammdaten, unter dem Reiter API-Key. <a href="https://www.shopgate.com/merchant/apikey" target="_blank">https://www.shopgate.com/merchant/apikey</a>',
    'MARM_SHOPGATE_CONFIG_GENERATE_ITEMS_CSV_ON_THE_FLY'        => 'CSV-Datei on the fly',
    'MARM_SHOPGATE_CONFIG_GENERATE_ITEMS_CSV_ON_THE_FLY_HELP'   => 'Soll die CSV-Datei direkt beim Aufruf der API genereiert werden? Bei gro&szlig;en Artikelmengen empfielt es sich die Datei vorab per Cronjob zu erstellen.',
    'MARM_SHOPGATE_CONFIG_GROUP_PERMISSIONS'    				=> 'Freigaben',
    'MARM_SHOPGATE_CONFIG_ENABLE_PING'          				=> 'Ping',
    'MARM_SHOPGATE_CONFIG_ENABLE_GET_SHOP_INFO' 				=> 'Shop Info',
    'MARM_SHOPGATE_CONFIG_ENABLE_HTTP_ALERT'        			=> 'Bestellungen',
    'MARM_SHOPGATE_CONFIG_ENABLE_HTTP_ALERT_HELP'   			=> 'Bestellungen automatisch von Shopgate importieren?',
    'MARM_SHOPGATE_CONFIG_ENABLE_GET_ITEMS_CSV' 				=> 'Artikelexport',
    'MARM_SHOPGATE_CONFIG_ENABLE_CONNECT'       				=> 'Kundenlogin (Single-Sign-On)',
    'MARM_SHOPGATE_CONFIG_ENABLE_CONNECT_HELP'  				=> 'Kunden k&ouml;nnen sich mit dem bestehenden OXID Kundenkonto bei Shopgate anmelden.',
    'MARM_SHOPGATE_CONFIG_GROUP_MOBILEWEB'      				=> 'mobile Website',
    'MARM_SHOPGATE_CONFIG_ENABLE_MOBILE_WEBSITE' 				=> 'Mobile Website aktivieren',
    'MARM_SHOPGATE_CONFIG_ENABLE_MOBILE_WEBSITE_HELP' 			=> 'Aktivieren Sie diese Einstellung, wenn Sie m&ouml;chten, dass mobile Endger&auml;te (iPhone, Android, etc) beim Betreten Ihrer Webseite die mobile Version Ihres Shops angezeigt werden soll. Die mobile Version ist f&uuml;r diesen Endger&auml;tetyp optimiert und &auml;hnelt der App(WebApp). Der Besucher hat jederzeit die M&ouml;glichkeit auf die herk&ouml;mmliche Version Ihrer Webseite zu wechseln.',
    'MARM_SHOPGATE_CONFIG_GROUP_DEBUG'          				=> 'Servereinstellungen',
    'MARM_SHOPGATE_CONFIG_SERVER'               				=> 'Serverumgebung',
    'MARM_SHOPGATE_CONFIG_SERVER_LIVE'          				=> 'Live-System (Live)',
    'MARM_SHOPGATE_CONFIG_SERVER_PG'            				=> 'Test-Sytem (Playground)',
    'MARM_SHOPGATE_CONFIG_SERVER_CUSTOM'        				=> 'Eigener',
    'MARM_SHOPGATE_CONFIG_SERVER_CUSTOM_URL'    				=> 'Individuelle Server URL',
    'MARM_SHOPGATE_CONFIG_SERVERUMG_HELP'    			    	=> 'Diese Einstellung legt fest, mit welchem Server das Plugin kommunizieren soll. &Uuml;blicherweise wird hier das Shopgate &quot;Live-System&quot; verwendet. Falls Sie zuerst das Shopgate &quot;Test-System&quot; (Playground) ausprobieren m&ouml;chten und Ihrem Shop dort angelegt und registriert haben, w&auml;hlen Sie dieses aus. Die Auswahl &quot;Eigener&quot; f&uuml;hrt zur Kommunikation des Plugins mit einem eigenen Server, dessen URL unten angegeben wird.',
);