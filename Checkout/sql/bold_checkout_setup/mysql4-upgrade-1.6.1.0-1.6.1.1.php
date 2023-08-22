<?php

$installer = $this;
$installer->startSetup();

// @phpcs:disable
$query = "
create table if not exists {$this->getTable(Bold_Checkout_Model_RegionCodeMapper::RESOURCE)}
(
    " . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . " varchar(4) not null comment 'Magento Internal Country Code',
    " . Bold_Checkout_Model_RegionCodeMapper::COLUMN_REGION_CODE . " varchar(32) not null comment 'Magento Internal Region Code',
    " . Bold_Checkout_Model_RegionCodeMapper::COLUMN_ISO_CODE . " varchar(2) not null comment 'Bold Checkout Order Fulfilment Status',
    constraint UNQ_BOLD_CHECKOUT_COUNTRY_REGION_CODE unique (" . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . ", " . Bold_Checkout_Model_RegionCodeMapper::COLUMN_REGION_CODE . "), 
    constraint UNQ_BOLD_CHECKOUT_COUNTRY_ISO_CODE unique (" . Bold_Checkout_Model_RegionCodeMapper::COLUMN_COUNTRY_CODE . ", " . Bold_Checkout_Model_RegionCodeMapper::COLUMN_ISO_CODE . ")
) engine=InnoDB DEFAULT charset=utf8 comment='Sales Order Additional Bold Data.';
";
// @phpcs:enable
$installer->run($query);

$installer->endSetup();
