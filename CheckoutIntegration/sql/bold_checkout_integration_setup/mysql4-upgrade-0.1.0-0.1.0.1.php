<?php

$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable(Bold_CheckoutIntegration_Model_Integration::RESOURCE);
if ($installer->getConnection()->isTableExists($installer->getTable(Bold_CheckoutIntegration_Model_Integration::RESOURCE))) {
    $sql = "ALTER TABLE {$tableName} ADD COLUMN website_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Website ID'";
}
$installer->run($sql);

$installer->endSetup();
