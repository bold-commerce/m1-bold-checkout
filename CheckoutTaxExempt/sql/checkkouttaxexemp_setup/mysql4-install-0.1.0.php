<?php
$installer = $this;
$installer->startSetup();

$boldCheckoutOrderTable = $this->getTable(Bold_Checkout_Model_Order::RESOURCE);
$installer->run(
    "ALTER TABLE {$boldCheckoutOrderTable} 
    ADD COLUMN is_tax_exempt SMALLINT(1) NOT NULL DEFAULT 0 COMMENT 'Is Tax Exempt';"
);
$installer->run(
    "ALTER TABLE {$boldCheckoutOrderTable}
    ADD COLUMN tax_exempt_file VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tax Exempt File';"
);
$installer->run(
    "ALTER TABLE {$boldCheckoutOrderTable}
    ADD COLUMN tax_exempt_comment VARCHAR(255) NULL DEFAULT NULL COMMENT 'Tax Exempt Comment';"
);

$installer->endSetup();
