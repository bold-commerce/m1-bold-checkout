<?php
// @phpcs:disable MEQP1.CodeAnalysis.EmptyBlock.DetectedCatch
$installer = $this;
$installer->startSetup();

$boldCheckoutSynchronizationTable = $this->getTable(Bold_Checkout_Model_Synchronization::RESOURCE);
try {
    $installer->run(
        "ALTER TABLE {$boldCheckoutSynchronizationTable} 
    ADD COLUMN website_id SMALLINT NOT NULL DEFAULT 0 COMMENT 'Entity Website ID';"
    );
} catch (Exception $exception) {
    // Column already added, do nothing.
}

try {
    $installer->run(
        "ALTER TABLE {$boldCheckoutSynchronizationTable} 
    DROP INDEX UNQ_BOLD_CHECKOUT_SYNCHRONIZATION_ENTITY_ENTITY_TYPE_ENTITY_ID;"
    );
} catch (Exception $exception) {
    // Index already removed, do nothing.
}

try {
    $installer->run(
        "ALTER TABLE {$boldCheckoutSynchronizationTable} 
    ADD CONSTRAINT UNQ_BOLD_CHECKOUT_SYNCHRONIZATION UNIQUE (entity_type, entity_id, website_id);"
    );
} catch (Exception $exception) {
    // Constraint already created,do nothing.
}

$installer->endSetup();
