<?php

/**
 * Update products sync time to re-sync ones via cron with bold service.
 */
class Bold_Checkout_Observer_ProductObserver
{
    /**
     * Set product sync time to null in order to sync product with bold via cron after product has been saved..
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function productSaved(Varien_Event_Observer $event)
    {
        $product = $event->getDataObject();
        if (Bold_Checkout_Service_ProductHasVariants::verify($product)) {
            return;
        }
        $storeId = Mage::app()->getRequest()->getParam('store');
        $websites = $storeId
            ? [Mage::app()->getStore($storeId)->getWebsite()]
            : Mage::app()->getWebsites();
        $productIds = [$product->getId()];
        foreach ($websites as $website) {
            $this->updateProducts($website->getId(), $productIds);
        }
    }

    /**
     * Set products sync time to null in order to sync product with bold via cron after product attribute mass update.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     * @phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
     */
    public function productMassAttributeUpdateAfter(Varien_Event_Observer $event)
    {
        /** @var Mage_Adminhtml_Helper_Catalog_Product_Edit_Action_Attribute $controllerAction */
        $actionAttribute = Mage::helper('adminhtml/catalog_product_edit_action_attribute');
        $productIds = $actionAttribute->getProductIds();
        if (empty($productIds)) {
            return;
        }
        $request = $event->getControllerAction()->getRequest();
        $websiteIdsToUpdate = $request->getPost('add_website_ids', []);
        foreach ($websiteIdsToUpdate as $websiteIdToUpdate) {
            $this->updateProducts($websiteIdToUpdate, $productIds);
        }
        $websiteIdsToRemove = $request->getPost('remove_website_ids', []);
        foreach ($websiteIdsToRemove as $websiteIdToRemove) {
            $this->deleteProducts($websiteIdToRemove, $productIds);
        }
        if ($websiteIdsToRemove || $websiteIdsToUpdate) {
            return;
        }
        $storeId = $request->getParam('store');
        $websites = $storeId
            ? [Mage::app()->getStore($storeId)->getWebsite()]
            : Mage::app()->getWebsites();

        foreach ($websites as $website) {
            $this->updateProducts($website->getId(), $productIds);
        }
    }

    /**
     * Set products sync time to null in order to sync product with bold via cron after product status mass update.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function productMassStatusUpdateAfter(Varien_Event_Observer $event)
    {
        $request = $event->getControllerAction()->getRequest();
        $productIds = $request->getParam('product');
        if (empty($productIds)) {
            return;
        }
        $storeId = $request->getParam('store');
        $websites = $storeId
            ? [Mage::app()->getStore($storeId)->getWebsite()]
            : Mage::app()->getWebsites();
        foreach ($websites as $website) {
            $this->updateProducts($website->getId(), $productIds);
        }
    }

    /**
     * Set product sync time to null in order to sync product with bold via cron after import.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function afterProductImport(Varien_Event_Observer $event)
    {
        /** @var Mage_ImportExport_Model_Import_Entity_Product $adapter */
        $adapter = $event->getAdapter();
        if ($adapter->getBehavior() === Mage_ImportExport_Model_Import::BEHAVIOR_DELETE) {
            $this->syncProductsDelete($adapter);
            return;
        }

        $productIds = $adapter->getAffectedEntityIds();
        if (empty($productIds)) {
            return;
        }
        foreach (Mage::app()->getWebsites() as $website) {
            $this->updateProducts($website->getId(), $productIds);
        }
    }

    /**
     * Set product sync time to null in order to sync product with bold via cron ofter product has been deleted.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    public function productDeleted(Varien_Event_Observer $event)
    {
        foreach (Mage::app()->getWebsites() as $website) {
            $this->deleteProducts($website->getId(), [$event->getDataObject()->getId()]);
        }
    }

    /**
     * Set product sync time to null in order to sync product with bold via cron after products have been deleted.
     *
     * @param Mage_ImportExport_Model_Import_Entity_Product $adapter
     * @return void
     */
    private function syncProductsDelete(Mage_ImportExport_Model_Import_Entity_Product $adapter)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $skus = $adapter->getOldSku();
        while ($bunch = $adapter->getNextBunch()) {
            $ids = [];
            foreach ($bunch as $rowNum => $rowData) {
                if ($this->shouldBeDeleted($adapter, $rowData, $rowNum)) {
                    $ids[] = $skus[$rowData[Mage_ImportExport_Model_Import_Entity_Product::COL_SKU]]['entity_id'];
                }
            }
            if ($ids) {
                foreach (Mage::app()->getWebsites() as $website) {
                    $websiteId = (int)$website->getId();
                    if (!$config->isCheckoutEnabled($websiteId)) {
                        continue;
                    }
                    if ($config->isRealtimeEnabled($websiteId)) {
                        Bold_Checkout_Service_Deleter::deleteEntities(
                            $ids,
                            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
                            $websiteId
                        );
                        continue;
                    }
                    // phpcs:ignore MEQP1.Performance.Loop.ModelLSD
                    Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                        $ids,
                        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
                        $websiteId,
                        null
                    );
                }
            }
        }
    }

    /**
     * Verify if id should be synced as deleted.
     *
     * @param Mage_ImportExport_Model_Import_Entity_Product $adapter
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    private function shouldBeDeleted(Mage_ImportExport_Model_Import_Entity_Product $adapter, array $rowData, $rowNum)
    {
        if (!$adapter->validateRow($rowData, $rowNum)) {
            return false;
        }

        return Mage_ImportExport_Model_Import_Entity_Product::SCOPE_DEFAULT === $adapter->getRowScope($rowData);
    }

    /**
     * Update products on Bold side.
     *
     * @param int $websiteId
     * @param array $productIds
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    private function updateProducts($websiteId, array $productIds)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled((int)$websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled((int)$websiteId)) {
            Bold_Checkout_Service_Synchronizer::synchronizeEntities(
                $productIds,
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
                (int)$websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            $productIds,
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
            (int)$websiteId,
            null
        );
    }

    /**
     * Delete products on Bold side.
     *
     * @param int $websiteId
     * @param array $productIds
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    private function deleteProducts($websiteId, array $productIds)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled((int)$websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled((int)$websiteId)) {
            Bold_Checkout_Service_Deleter::deleteEntities(
                $productIds,
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
                (int)$websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            $productIds,
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT,
            (int)$websiteId,
            null
        );
    }
}
