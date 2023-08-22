<?php
// @phpcs:disable MEQP1.CodeAnalysis.EmptyBlock.DetectedCatch
$installer = $this;
$installer->startSetup();

$boldCheckoutOrderTable = $this->getTable(Bold_Checkout_Model_Order::RESOURCE);
try {
    $installer->run(
        "ALTER TABLE {$boldCheckoutOrderTable} 
    ADD COLUMN is_delayed_capture SMALLINT NOT NULL DEFAULT 0 COMMENT 'Is Order Using Delayed Payment Capture.';"
    );
} catch (Exception $exception) {
    // Column already added, do nothing.
}

$installer->endSetup();
