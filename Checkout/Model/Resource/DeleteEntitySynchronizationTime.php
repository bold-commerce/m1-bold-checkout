<?php

/**
 * Delete entities synchronization time.
 */
class Bold_Checkout_Model_Resource_DeleteEntitySynchronizationTime
{
    /**
     * Delete entities synchronization time.
     *
     * @param int[] $entityIds
     * @param string $entityType
     * @return void
     * @throws Zend_Db_Exception
     */
    public static function delete(array $entityIds, $entityType)
    {
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
        $synchronizationTable = $resource->getTableName('bold_checkout_synchronization_entity');
        $where = [
            Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE . ' =  ?' => $entityType,
            Bold_Checkout_Model_Resource_Synchronization::ENTITY_ID . ' IN (?)' => $entityIds
        ];
        $connection->delete(
            $synchronizationTable,
            $where
        );
    }
}
