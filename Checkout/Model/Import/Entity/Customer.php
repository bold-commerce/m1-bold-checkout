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
        if ($this->getBehavior() === Mage_ImportExport_Model_Import::BEHAVIOR_DELETE) {
            $this->syncDeletedCustomers();
        }
        parent::_importData();
        if ($this->getBehavior() !== Mage_ImportExport_Model_Import::BEHAVIOR_DELETE) {
            $this->syncImportedCustomers();
        }
        return true;
    }

    /**
     * Retrieve imported customers.
     *
     * @return void
     */
    private function syncImportedCustomers()
    {
        $websiteIds = [];
        /** @var Mage_Customer_Model_Config_Share $configShare */
        $configShare = Mage::getSingleton('customer/config_share');
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteIds[] = $website->getId();
        }
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $emails = $this->getEmails($bunch);
            if (!$emails) {
                continue;
            }
            if ($configShare->isWebsiteScope()) {
                $this->syncImportedCustomersPerWebsite($emails);
                continue;
            }
            foreach ($websiteIds as $websiteId) {
                $queryParameters = [
                    'searchCriteria' => [
                        'filterGroups' => [
                            [
                                'filters' => [
                                    [
                                        'field' => 'email',
                                        'conditionType' => 'in',
                                        'value' => $emails,
                                    ],
                                ],
                            ],
                        ],
                        'pageSize' => count($emails),
                        'currentPage' => 1,
                    ],
                ];
                Bold_Checkout_Api_Bold_Customers::update($queryParameters, $websiteId);
            }
        }
    }

    /**
     * Retrieve imported customers.
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    private function syncDeletedCustomers()
    {
        /** @var Mage_Customer_Model_Config_Share $configShare */
        $configShare = Mage::getSingleton('customer/config_share');
        $websiteIds = [];
        foreach (Mage::app()->getWebsites() as $website) {
            $websiteIds[] = $website->getId();
        }
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $emails = $this->getEmails($bunch);
            if (!$emails) {
                continue;
            }
            /** @var Mage_Customer_Model_Resource_Customer_Collection $collection */
            $collection = Mage::getModel('customer/customer')->getCollection();
            $collection->addFieldToFilter('email', $emails);
            $collection->addAttributeToSelect('website_id');
            if ($configShare->isWebsiteScope()) {
                $this->syncDeletedCustomersPerWebsite($collection);
                continue;
            }
            $customerIds[] = $collection->getAllIds();
            foreach ($websiteIds as $websiteId) {
                Bold_Checkout_Api_Bold_Customers::deleted($customerIds, $websiteId);
            }
        }
    }

    /**
     * Get imported customer's emails.
     *
     * @param array $bunch
     * @return array
     */
    private function getEmails(array $bunch)
    {
        $emails = [];
        foreach ($bunch as $rowNum => $rowData) {
            if (!$this->validateRow($rowData, $rowNum)) {
                continue;
            }
            if (self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
                $emails[] = strtolower($rowData[self::COL_EMAIL]);
            }
        }
        return $emails;
    }

    /**
     * Sync deleted customers with Bold.
     *
     * @param Mage_Customer_Model_Resource_Customer_Collection $collection
     * @return void
     * @throws Mage_Core_Exception
     */
    private function syncDeletedCustomersPerWebsite(Mage_Customer_Model_Resource_Customer_Collection $collection)
    {
        $ids = [];
        foreach ($collection->getItems() as $customer) {
            $ids[$customer->getWebsiteId()][] = $customer->getId();
        }
        foreach ($ids as $websiteId => $customerIds) {
            Bold_Checkout_Api_Bold_Customers::deleted($customerIds, $websiteId);
        }
    }

    /**
     * Sync imported customers with Bold.
     *
     * @param array $emails
     * @return void
     * @throws Mage_Core_Exception
     */
    private function syncImportedCustomersPerWebsite(array $emails)
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection $collection */
        $collection = Mage::getModel('customer/customer')->getCollection();
        $collection->addFieldToFilter('email', $emails);
        $collection->addAttributeToSelect('website_id');
        $ids = [];
        foreach ($collection->getItems() as $customer) {
            $ids[$customer->getWebsiteId()][] = $customer->getId();
        }
        foreach ($ids as $websiteId => $customerIds) {
            $queryParameters = [
                'searchCriteria' => [
                    'filterGroups' => [
                        [
                            'filters' => [
                                [
                                    'field' => 'entity_id',
                                    'conditionType' => 'in',
                                    'value' => $customerIds,
                                ],
                                [
                                    'field' => 'website_id',
                                    'conditionType' => 'eq',
                                    'value' => $websiteId,
                                ],
                            ],
                        ],
                    ],
                    'pageSize' => count($customerIds),
                    'currentPage' => 1,
                ],
            ];
            Bold_Checkout_Api_Bold_Customers::update($queryParameters, $websiteId);
        }
    }
}
