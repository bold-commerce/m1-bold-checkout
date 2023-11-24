<?php

$installer = $this;
$installer->startSetup();

$tableName = $installer->getTable(Bold_Checkout_Model_Order::RESOURCE);

$removeFinancialStatusColumn = "ALTER TABLE {$tableName} DROP COLUMN financial_status;";
$removeFulfillmentStatusColumn = "ALTER TABLE {$tableName} DROP COLUMN fulfillment_status;";

$installer->run($removeFinancialStatusColumn);
$installer->run($removeFulfillmentStatusColumn);

$installer->endSetup();
