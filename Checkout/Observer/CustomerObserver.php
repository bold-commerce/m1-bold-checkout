<?php

/**
 * Update customer sync time to re-sync ones via cron with bold service.
 */
class Bold_Checkout_Observer_CustomerObserver
{
    /**
     * Set customer sync time to null in order to sync customer with bold via cron after customer has been saved.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function customerSaved(Varien_Event_Observer $event)
    {
        $customer = $event->getCustomer();
        $websiteIds = $this->getWebsiteIds($customer);
        foreach ($websiteIds as $websiteId) {
            $this->syncCustomerSave($customer->getId(), $websiteId);
        }
    }

    /**
     * Set customer sync time to null in order to sync customer with bold via cron after customer has been deleted.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function customerDeleted(Varien_Event_Observer $event)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $event->getDataObject();
        $websiteIds = $this->getWebsiteIds($customer);
        foreach ($websiteIds as $websiteId) {
            $this->syncCustomerDelete($customer->getId(), $websiteId);
        }
    }

    /**
     * Set customer sync time to null in order to sync customer with bold via cron when customer address has been saved.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function customerAddressSaved(Varien_Event_Observer $event)
    {
        $customerId = $event->getDataObject()->getCustomerId();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getId()) {
            return;
        }
        $websiteIds = $this->getWebsiteIds($customer);
        foreach ($websiteIds as $websiteId) {
            $this->syncCustomerSave($customer->getId(), $websiteId);
        }
    }

    /**
     * Set customer sync time to null in order to sync one with bold via cron when customer address has been deleted.
     *
     * @param Varien_Event_Observer $event
     * @return void
     * @throws Exception
     */
    public function customerAddressDeleted(Varien_Event_Observer $event)
    {
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $websiteId = Mage::app()->getWebsite()->getId();
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        $customerId = $event->getDataObject()->getCustomerId();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer = $customer->load($customerId);
        if (!$customer->getId()) {
            return;
        }
        $websiteIds = $this->getWebsiteIds($customer);
        foreach ($websiteIds as $websiteId) {
            $this->syncCustomerSave($customerId, $websiteId);
        }
    }

    /**
     * Get customer websites.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    private function getWebsiteIds(Mage_Customer_Model_Customer $customer)
    {
        $websitesIds = [];
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            return [$customer->getWebsiteId()];
        }
        foreach (Mage::app()->getWebsites() as $website) {
            $websitesIds[] = $website->getId();
        }
        return $websitesIds;
    }

    /**
     * Sync customer with Bold after save.
     *
     * @param int $customerId
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    private function syncCustomerSave($customerId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled($websiteId)) {
            Bold_Checkout_Service_Synchronizer::synchronizeEntities(
                [$customerId],
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER,
                $websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            [$customerId],
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER,
            $websiteId,
            null
        );
    }

    /**
     * Sync customer with Bold after delete.
     *
     * @param int $customerId
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     * @throws Zend_Db_Exception
     */
    public function syncCustomerDelete($customerId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        if ($config->isRealtimeEnabled($websiteId)) {
            Bold_Checkout_Service_Deleter::deleteEntities(
                [$customerId],
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER,
                $websiteId
            );
            return;
        }
        Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
            [$customerId],
            Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER,
            $websiteId,
            null
        );
    }
}
