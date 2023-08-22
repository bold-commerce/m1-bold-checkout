<?php

/**
 * Populate bold_checkout_synchronization_status table.
 */
foreach (Bold_Checkout_Service_Synchronizer::ENTITY_TYPES as $entityType) {
    /** @var Bold_Checkout_Model_Status $entity */
    $entity = Mage::getModel(Bold_Checkout_Model_Status::RESOURCE);
    $entity->load($entityType, Bold_Checkout_Model_Resource_Status::ENTITY_TYPE);
    if ($entity->getId()) {
        continue;
    }
    $entity->setEntityType($entityType);
    $entity->setStatus(Bold_Checkout_Model_Status::STATUS_READY);
    //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
    $entity->save();
}
