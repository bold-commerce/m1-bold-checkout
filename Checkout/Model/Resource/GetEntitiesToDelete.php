<?php

/**
 * Get entities, deleted in Magento but still existing on Bold.
 */
class Bold_Checkout_Model_Resource_GetEntitiesToDelete
{
    const ENTITY_TABLES = [
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER => 'customer_entity',
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY => 'catalog_category_entity',
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT => 'catalog_product_entity',
    ];

    /**
     * Get list of entities ids to delete.
     *
     * @param string $entityType
     * @return int[]
     * @throws Mage_Core_Exception
     */
    public static function getIdsList($entityType)
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $entityResourceTable = $resource->getTableName(self::getEntityTable($entityType));
        $synchronizationTable = $resource->getTableName('bold_checkout_synchronization_entity');
        $select = $connection->select();
        $select->from(
            ['main' => $synchronizationTable],
            [
                'entity_id',
            ]
        )->joinLeft(
            ['entity' => $entityResourceTable],
            'entity.entity_id = main.entity_id',
            []
        )->where(
            'main.' . Bold_Checkout_Model_Resource_Synchronization::ENTITY_TYPE . ' = ?', $entityType
        )->where(
            'entity.entity_id IS NULL'
        )->order(
            'main.' . Bold_Checkout_Model_Resource_Synchronization::SYNCHRONIZED_AT . ' ' . Zend_Db_Select::SQL_ASC
        );
        return $connection->fetchCol($select, 'entity_id');
    }

    /**
     * Get entity table by entity type.
     *
     * @param string $entityType
     * @return string
     * @throws Mage_Core_Exception
     */
    private static function getEntityTable($entityType)
    {
        $entityTables = self::ENTITY_TABLES;
        if (!isset($entityTables[$entityType])) {
            Mage::throwException(
                Mage::helper('cron')
                    ->__(
                        'Deletion error: entity type \'%s\' not expected.',
                        $entityType
                    )
            );
        }

        return $entityTables[$entityType];
    }
}
