<?php

/**
 * Synchronize category with Bold after save|delete.
 */
class Bold_Checkout_Observer_CategoryObserver
{
    /**
     * Sync category with bold after category has been saved.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function categorySaved(Varien_Event_Observer $event)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            $this->syncCategorySave($event->getCategory()->getId(), $website->getId());
        }
    }

    /**
     * Sync category with bold after category has been deleted.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function categoryDeleted(Varien_Event_Observer $event)
    {
        $categoryId = $event->getDataObject()->getId();
        foreach (Mage::app()->getWebsites() as $website) {
            $this->syncCategoryDelete($categoryId, $website->getId());
        }
    }

    /**
     * Sync category for given website with Bold after category has been saved.
     *
     * @param int $categoryId
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    public function syncCategorySave($categoryId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled($websiteId)) {
            Bold_Checkout_Service_Synchronizer::synchronizeEntities(
                [$categoryId],
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY,
                $websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            [$categoryId],
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY,
            $websiteId,
            null
        );
    }

    /**
     * Sync category for given website with Bold after category has been deleted.
     *
     * @param int $categoryId
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    public function syncCategoryDelete($categoryId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled($websiteId)) {
            Bold_Checkout_Service_Deleter::deleteEntities(
                [$categoryId],
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY,
                $websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            [$categoryId],
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY,
            $websiteId,
            null
        );
    }
}
