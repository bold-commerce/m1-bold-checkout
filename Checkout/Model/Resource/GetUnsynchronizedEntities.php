<?php

/**
 * Get not synchronized entities.
 */
class Bold_Checkout_Model_Resource_GetUnsynchronizedEntities
{
    const ENTITY_TABLES = [
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER => 'customer_entity',
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY => 'catalog_category_entity',
        Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT => 'catalog_product_entity',
    ];

    /**
     * Get list of not synchronized entities ids.
     *
     * @param string $entityType
     * @return array[]
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
            ['main' => $entityResourceTable],
            [
                'entity_id',
            ]
        )->joinLeft(
            ['sync' => $synchronizationTable],
            'sync.entity_id = main.entity_id',
            [
                'website_id',
            ]
        )->where('sync.synchronized_at < main.updated_at OR sync.synchronized_at IS NULL')
            ->order('main.updated_at ' . Zend_Db_Select::SQL_ASC);
        //phpcs:ignore MEQP1.Performance.InefficientMethods.FoundFetchAll
        $rows = $connection->fetchAll($select) ?: [];
        $result = [];
        foreach ($rows as $row) {
            if ($row['website_id']) {
                $result[$row['website_id']][] = $row['entity_id'];
                continue;
            }
            foreach (Mage::app()->getWebsites() as $website) {
                $result[$website->getId()][] = $row['entity_id'];
            }
        }
        return $result;
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
                        'Synchronization error: entity type \'%s\' not expected.',
                        $entityType
                    )
            );
        }

        return $entityTables[$entityType];
    }
}
