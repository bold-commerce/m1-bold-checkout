<?php

/**
 * Model to delete on Bold all entities, deleted in Magento.
 */
class Bold_Checkout_Model_Cron_Deletion
{
    /**
     * Delete entities.
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
            $entityIds = Bold_Checkout_Model_Resource_GetEntitiesToDelete::getIdsList($entityType);
            if (empty($entityIds)) {
                continue;
            }
            try {
                $statusEntity->lock();
                foreach (Mage::app()->getWebsites() as $website) {
                    Bold_Checkout_Service_Deleter::deleteEntities(
                        $entityIds,
                        $entityType,
                        $website->getId()
                    );
                }
            } finally {
                $statusEntity->unlock();
            }
        }
    }
}
