ALTER TABLE `oxarticles` ADD `marm_shopgate_marketplace` TINYINT UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `oxorder` ADD `marm_shopgate_order_number` INT UNSIGNED NOT NULL ;

INSERT INTO `oxpayments` (`OXID`, `OXACTIVE`, `OXDESC`, `OXADDSUM`, `OXADDSUMTYPE`, `OXFROMBONI`, `OXFROMAMOUNT`, `OXTOAMOUNT`, `OXVALDESC`, `OXCHECKED`, `OXDESC_1`, `OXVALDESC_1`, `OXDESC_2`, `OXVALDESC_2`, `OXDESC_3`, `OXVALDESC_3`, `OXLONGDESC`, `OXLONGDESC_1`, `OXLONGDESC_2`, `OXLONGDESC_3`, `OXSORT`, `OXTSPAYMENTID`) VALUES ('oxshopgate', '1', 'Shopgate', '0', 'abs', '0', '0', '100000', 'shopgate', '0', '', '', '', '', '', '', 'Bezahlt bei Shopgate', '', '', '', '0', '');
