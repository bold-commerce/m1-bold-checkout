<?php

/**
 * Entity deleter service.
 */
class Bold_Checkout_Service_Deleter
{
    const CHUNK_SIZE = 100;

    /**
     * Delete entities by type and ids.
     *
     * @param int[] $entityIds
     * @param string $entityType
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function deleteEntities(array $entityIds, $entityType, $websiteId)
    {
        switch ($entityType) {
            case Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY:
                self::deleteCategories($entityIds, $websiteId);
                break;
            case Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER:
                self::deleteCustomers($entityIds, $websiteId);
                break;
            case Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT:
                self::deleteProducts($entityIds, $websiteId);
                break;
            default:
                Mage::throwException(
                    Mage::helper('cron')
                        ->__(
                            'Deletion error: entity type \'%s\' not expected.',
                            $entityType
                        )
                );
        }
    }

    /**
     * Delete Category entities by id.
     *
     * @param int[] $categoryIds
     * @param int $websiteId
     * @return void
     * @throws \Exception
     */
    private static function deleteCategories(array $categoryIds, $websiteId)
    {
        $categoryIdsChunks = array_chunk($categoryIds, self::CHUNK_SIZE);
        foreach ($categoryIdsChunks as $entityIds) {
            if (!$entityIds) {
                return;
            }
            foreach ($entityIds as $entityId) {
                Bold_Checkout_Api_Bold_Categories::deleted($entityId, $websiteId);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_DeleteEntitySynchronizationTime::delete(
                $entityIds,
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CATEGORY
            );
        }
    }

    /**
     * Delete Customer entities by id.
     *
     * @param int[] $customerIds
     * @param int $websiteId
     * @return void
     * @throws Exception
     */
    private static function deleteCustomers(array $customerIds, $websiteId)
    {
        $customerIdsChunks = array_chunk($customerIds, self::CHUNK_SIZE);
        foreach ($customerIdsChunks as $entityIds) {
            if (!$entityIds) {
                return;
            }
            foreach ($entityIds as $entityId) {
                Bold_Checkout_Api_Bold_Customers::deleted($entityId, $websiteId);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_DeleteEntitySynchronizationTime::delete(
                $entityIds,
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER
            );
        }
    }

    /**
     * Delete Product entities by id.
     *
     * @param int[] $productIds
     * @param int $websiteId
     * @return void
     * @throws Exception
     */
    private static function deleteProducts(array $productIds, $websiteId)
    {
        $productIdsChunks = array_chunk($productIds, self::CHUNK_SIZE);
        foreach ($productIdsChunks as $entityIds) {
            if (!$entityIds) {
                return;
            }
            foreach ($entityIds as $entityId) {
                Bold_Checkout_Api_Bold_Products::deleted($entityId, $websiteId);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_DeleteEntitySynchronizationTime::delete(
                $entityIds,
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_PRODUCT
            );
        }
    }
}
