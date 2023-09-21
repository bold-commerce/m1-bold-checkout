<?php

$installer = $this;
$installer->startSetup();

if (!$installer->getConnection()->isTableExists($installer->getTable(Bold_Checkout_Model_Resource_Order_ProgressResource::TABLE))) {
    $installer->run("
    CREATE TABLE `{$installer->getTable(Bold_Checkout_Model_Resource_Order_ProgressResource::TABLE)}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
        `quote_id` int(10) unsigned NOT NULL COMMENT 'Magento Quote ID',
        PRIMARY KEY (`id`),
        INDEX `BOLD_CHECKOUT_QUOTE_PROGRESS_QUOTE_ID` (`quote_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Is Bold Checkout Order In Progress';
");
}
$installer->endSetup();
