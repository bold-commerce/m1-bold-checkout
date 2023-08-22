<?php

/**
 * Get parent Product ids by child ids.
 */
class Bold_Checkout_Model_Resource_GetProductParentIdsByChildrenIds
{
    /**
     * Get parent Product ids by child ids.
     *
     * @param array $childrenIds
     * @return array
     */
    public static function getParentIds(array $childrenIds)
    {
        $result = [];
        if (!empty($childrenIds)) {
            $resource = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
            $catalogProductSuperLink = $resource->getTableName('catalog_product_super_link');
            $select = $connection->select()
                ->from($catalogProductSuperLink, ['product_id', 'parent_id'])
                ->where('product_id IN(?)', $childrenIds);
            //phpcs:ignore MEQP1.Performance.InefficientMethods.FoundFetchAll
            foreach ($connection->fetchAll($select) as $row) {
                $result[$row['product_id']][] = $row['parent_id'];
            }
        }

        return $result;
    }
}
