<?php

/**
 * Model to synchronize all new/updated entities.
 */
class Bold_Checkout_Model_Cron_Synchronization
{
    /**
     * Synchronize all new/updated entities.
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    public function run()
    {
        $statusEntities = Mage::getModel(Bold_Checkout_Model_Status::RESOURCE)->getCollection();
        /** @var Bold_Checkout_Model_Status $statusEntity */
        foreach ($statusEntities as $statusEntity) {
            if ($statusEntity->isProcessing()) {
                continue;
            }
            $entityType = $statusEntity->getEntityType();
            $rows = Bold_Checkout_Model_Resource_GetUnsynchronizedEntities::getIdsList($entityType);
            if (empty($rows)) {
                continue;
            }
            try {
                $statusEntity->lock();
                foreach ($rows as $websiteId => $entityIds) {
                    Bold_Checkout_Service_Synchronizer::synchronizeEntities($entityIds, $entityType, $websiteId);
                }
            } finally {
                $statusEntity->unlock();
            }
        }
    }
}
