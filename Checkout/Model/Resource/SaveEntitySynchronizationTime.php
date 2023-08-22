<?php

/**
 * Save entities synchronization time.
 */
class Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime
{
    /**
     * Save entities synchronization time.
     *
     * @param int[] $entityIds
     * @param string $entityType
     * @param int $websiteId
     * @param string|null $date
     * @return void
     * @throws Zend_Db_Exception
     */
    public static function save(array $entityIds, $entityType, $websiteId, $date)
    {
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $synchronizationTable = $resource->getTableName('bold_checkout_synchronization_entity');
        $data = array_map(
            function ($entityId) use ($entityType, $websiteId, $date) {
                return [
                    Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE => $entityType,
                    Bold_Checkout_Model_Resource_Synchronization::ENTITY_ID => $entityId,
                    Bold_Checkout_Model_Resource_Synchronization::WEBSITE_ID => $websiteId,
                    Bold_Checkout_Model_Resource_Synchronization::SYNCHRONIZED_AT => $date
                ];
            },
            $entityIds
        );
        $fields = [
            Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE,
            Bold_Checkout_Model_Resource_Synchronization::ENTITY_ID,
            Bold_Checkout_Model_Resource_Synchronization::WEBSITE_ID,
            Bold_Checkout_Model_Resource_Synchronization::SYNCHRONIZED_AT
        ];
        $connection->insertOnDuplicate(
            $synchronizationTable,
            $data,
            $fields
        );
    }
}
