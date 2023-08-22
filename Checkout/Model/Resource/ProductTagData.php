<?php

/**
 * Load Product Tag Data.
 */
class Bold_Checkout_Model_Resource_ProductTagData
{
    /**
     * Load Product Tag Data.
     *
     * @param array $productIds
     * @return array
     */
    public static function getTags(array $productIds)
    {
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        /** @var Varien_Db_Select $select */
        $select = $connection->select()
            ->from(
                ['tag_relation' => $resource->getTableName('tag_relation')],
                ['tag_relation.product_id']
            )
            ->where(
                'tag_relation.active = 1 AND tag_relation.product_id IN (?)', $productIds
            )
            ->joinLeft(
                ['tag' => $resource->getTableName('tag')],
                'tag_relation.tag_id = tag.tag_id and tag.status = 1',
                ['tags' => new Zend_Db_Expr('GROUP_CONCAT(tag.name SEPARATOR ", ")')]
            )
            //phpcs:ignore MEQP1.SQL.SlowQuery.FoundSlowSql
            ->group('tag_relation.product_id');
        //phpcs:ignore MEQP1.Performance.InefficientMethods.FoundFetchAll
        $result = $connection->fetchAll($select);

        return array_combine(array_column($result, 'product_id'), array_column($result, 'tags'));
    }
}
