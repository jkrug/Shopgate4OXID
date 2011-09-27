ALTER TABLE `oxarticles` ADD `marm_shopgate_marketplace` TINYINT UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `oxorder` ADD `marm_shopgate_order_number` INT UNSIGNED NOT NULL ;
