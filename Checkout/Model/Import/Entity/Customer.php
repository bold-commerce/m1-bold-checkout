<?php

/**
 * Core customer import rewrite to add sync with bold logic.
 */
class Bold_Checkout_Model_Import_Entity_Customer extends Mage_ImportExport_Model_Import_Entity_Customer
{
    /**
     * Perform customer data sync with Bold after customer import.
     *
     * @return bool
     * @throws Exception
     */
    protected function _importData()
    {
        $customersToDelete = [];
        $customersToUpdate = [];
        if ($this->getBehavior() === Mage_ImportExport_Model_Import::BEHAVIOR_DELETE) {
            $customersToDelete = $this->getImportedCustomers();
        }
        parent::_importData();
        if ($this->getBehavior() !== Mage_ImportExport_Model_Import::BEHAVIOR_DELETE) {
            $customersToUpdate = $this->getImportedCustomers();
        }
        /** @var Mage_Customer_Model_Config_Share $configShare */
        $configShare = Mage::getSingleton('customer/config_share');
        $websiteIds = [];
        if (!$configShare->isWebsiteScope()) {
            foreach (Mage::app()->getWebsites() as $website) {
                $websiteIds[] = $website->getId();
            }
        }
        foreach ($customersToDelete as $customer) {
            $this->syncCustomerDelete($websiteIds, $customer);
        }
        foreach ($customersToUpdate as $customer) {
            $this->syncCustomerUpdate($websiteIds, $customer);
        }

        return true;
    }

    /**
     * Retrieve imported customers.
     *
     * @return Mage_Customer_Model_Customer[]
     */
    private function getImportedCustomers()
    {
        $customers = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $emails = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if (self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
                    $emails[] = strtolower($rowData[self::COL_EMAIL]);
                }
            }
            if (!$emails) {
                continue;
            }
            /** @var Mage_Reports_Model_Resource_Customer_Collection $collection */
            $collection = Mage::getModel('customer/customer')->getCollection();
            $collection->addFieldToFilter('email', $emails);
            $customers[] = $collection->getItems();
        }

        return array_merge(...$customers);
    }

    /**
     * Perform customer data sync with Bold after customer deleted.
     *
     * @param array $websiteIds
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     * @throws Mage_Core_Exception
     */
    private function syncCustomerDelete(array $websiteIds, Mage_Customer_Model_Customer $customer)
    {
        if (!$websiteIds) {
            Bold_Checkout_Api_Bold_Customers::deleted((int)$customer->getId(), $customer->getWebsiteId());
            return;
        }
        foreach ($websiteIds as $websiteId) {
            Bold_Checkout_Api_Bold_Customers::deleted((int)$customer->getId(), $websiteId);
        }
    }

    /**
     * Perform customer data sync with Bold after customer updated.
     *
     * @param array $websiteIds
     * @param Mage_Customer_Model_Customer $customer
     * @return void
     * @throws Exception
     */
    public function syncCustomerUpdate(array $websiteIds, Mage_Customer_Model_Customer $customer)
    {
        if (!$websiteIds) {
            Bold_Checkout_Api_Bold_Customers::updated($customer, $customer->getWebsiteId());
            return;
        }
        foreach ($websiteIds as $websiteId) {
            Bold_Checkout_Api_Bold_Customers::updated($customer, $websiteId);
        }
    }
}
