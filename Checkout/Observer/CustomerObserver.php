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
     */
    private function syncCustomerSave($customerId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        $queryParameters = [
            'searchCriteria' => [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'entity_id',
                                'conditionType' => 'eq',
                                'value' => $customerId,
                            ],
                            [
                                'field' => 'website_id',
                                'conditionType' => 'eq',
                                'value' => $websiteId,
                            ],
                        ],
                    ],
                ],
                'pageSize' => 1,
                'currentPage' => 1,
            ],
        ];
        $result = Bold_Checkout_Api_Bold_Customers::update($queryParameters, $websiteId);
        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        isset($result->errors)
            ? $session->addError(Mage::helper('core')->__('Cannot synchronize customer with Bold'))
            : $session->addNotice(Mage::helper('core')->__('Customer successfully synchronized with Bold'));
    }

    /**
     * Sync customer with Bold after delete.
     *
     * @param int $customerId
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    private function syncCustomerDelete($customerId, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$config->isCheckoutEnabled($websiteId)) {
            return;
        }
        $result = Bold_Checkout_Api_Bold_Customers::deleted([(int)$customerId], $websiteId);
        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        isset($result->errors)
            ? $session->addError(Mage::helper('core')->__('Cannot sync customer with Bold'))
            : $session->addNotice(Mage::helper('core')->__('Customer successfully synced with Bold'));
    }
}
