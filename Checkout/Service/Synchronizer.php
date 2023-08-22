<?php

/**
 * Entity synchronizer service.
 */
class Bold_Checkout_Service_Synchronizer
{
    const CHUNK_SIZE = 100;

    const ENTITY_TYPE_CUSTOMER = 'customer';
    const ENTITY_TYPE_CATEGORY = 'category';
    const ENTITY_TYPE_PRODUCT = 'product';

    const ENTITY_TYPES = [
        self::ENTITY_TYPE_CATEGORY,
        self::ENTITY_TYPE_CUSTOMER,
        self::ENTITY_TYPE_PRODUCT,
    ];
    const STATUS_SUCCESSFUL = 'success';
    const STATUS_FAILED = 'fail';

    /**
     * Synchronize entities by type and ids.
     *
     * @param int[] $entityIds
     * @param string $entityType
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function synchronizeEntities(array $entityIds, $entityType, $websiteId)
    {
        switch ($entityType) {
            case self::ENTITY_TYPE_CATEGORY:
                $status = self::synchronizeCategories($entityIds, $websiteId);
                break;
            case self::ENTITY_TYPE_CUSTOMER:
                $status = self::synchronizeCustomers($entityIds, $websiteId);
                break;
            case self::ENTITY_TYPE_PRODUCT:
                $status = self::synchronizeProducts($entityIds, $websiteId);
                break;
            default:
                Mage::throwException(
                    Mage::helper('core')->__(
                        'Synchronization error: entity type \'%s\' not expected.',
                        $entityType
                    )
                );
        }

        self::showResult($entityType, $status);
    }

    /**
     * Synchronize Category entities by id.
     *
     * @param int[] $categoryIds
     * @param int $websiteId
     * @return string
     * @throws Zend_Db_Exception
     */
    private static function synchronizeCategories(array $categoryIds, $websiteId)
    {
        $categoryIdsChunks = array_chunk($categoryIds, self::CHUNK_SIZE);
        $status = self::STATUS_SUCCESSFUL;
        foreach ($categoryIdsChunks as $entityIds) {
            if (!$entityIds) {
                return $status;
            }
            $categoryCollection = Mage::getModel('catalog/category')->getCollection();
            $categoryCollection->setStoreId(Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId());
            $categoryCollection->addAttributeToSelect(['name', 'image']);
            $categoryCollection->addFieldToFilter('entity_id', ['in' => $entityIds]);
            $date = Mage::getModel('core/date')->gmtDate();
            $status = self::STATUS_SUCCESSFUL;
            foreach ($categoryCollection->getItems() as $category) {
                $result = Bold_Checkout_Api_Bold_Categories::updated($category, $websiteId);
                $status = self::updateStatus($status, $result);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $entityIds,
                self::ENTITY_TYPE_CATEGORY,
                $websiteId,
                $date
            );
        }

        return $status;
    }

    /**
     * Synchronize Customer entities by id.
     *
     * @param int[] $customerIds
     * @param int $websiteId
     * @return string
     * @throws Exception
     */
    private static function synchronizeCustomers(array $customerIds, $websiteId)
    {
        $customerIdsChunks = array_chunk($customerIds, self::CHUNK_SIZE);
        $status = self::STATUS_SUCCESSFUL;
        foreach ($customerIdsChunks as $entityIds) {
            if (!$entityIds) {
                return $status;
            }
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer');
            $customerCollection = $customer->getCollection();
            if ($customer->getSharingConfig()->isWebsiteScope()) {
                $customerCollection->addAttributeToFilter(
                    'website_id',
                    $websiteId
                );
            }
            $customerCollection->addFieldToFilter('entity_id', ['in' => $entityIds]);
            $date = Mage::getModel('core/date')->gmtDate();
            $status = self::STATUS_SUCCESSFUL;
            foreach ($customerCollection->getItems() as $customer) {
                $result = Bold_Checkout_Api_Bold_Customers::updated($customer, $websiteId);
                $status = self::updateStatus($status, $result);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $entityIds,
                self::ENTITY_TYPE_CUSTOMER,
                $websiteId,
                $date
            );
        }
        return $status;
    }

    /**
     * Synchronize Product entities by id.
     *
     * @param int[] $productIds
     * @param int $websiteId
     * @return string
     * @throws Exception
     */
    private static function synchronizeProducts(array $productIds, $websiteId)
    {
        $productIdsChunks = array_chunk($productIds, self::CHUNK_SIZE);
        $status = self::STATUS_SUCCESSFUL;
        foreach ($productIdsChunks as $entityIds) {
            if (!$entityIds) {
                return $status;
            }
            $productCollection = Bold_Checkout_Model_Resource_ProductListBuilder::build(
                self::CHUNK_SIZE,
                1,
                $websiteId
            );
            $productCollection->addFieldToFilter('entity_id', ['in' => $entityIds]);
            $productCollection->setStoreId(Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId());
            $tags = Bold_Checkout_Model_Resource_ProductTagData::getTags([$entityIds]);
            Bold_Checkout_Service_MediaGalleryData::addToProducts($productCollection);
            $date = Mage::getModel('core/date')->gmtDate();
            foreach ($productCollection->getItems() as $product) {
                isset($tags[$product->getId()]) && $product->setTags($tags[$product->getId()]);
                $result = Bold_Checkout_Api_Bold_Products::updated($product, $websiteId);
                $status = self::updateStatus($status, $result);
            }
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $entityIds,
                self::ENTITY_TYPE_PRODUCT,
                $websiteId,
                $date
            );
        }

        return $status;
    }

    /**
     * Update synchronization status.
     *
     * @param string $actualStatus
     * @param string $callResult
     * @return string
     */
    private static function updateStatus($actualStatus, $callResult)
    {
        $error = !empty(json_decode($callResult, true));

        return $error ? self::STATUS_FAILED : $actualStatus;
    }

    /**
     * Render synchronization result in admin session.
     *
     * @param string $type
     * @param string $status
     * @return void
     */
    private static function showResult($type, $status)
    {
        switch ($status) {
            case self::STATUS_SUCCESSFUL:
                $message = sprintf('Bold Checkout: %s synchronization was successful.', $type);
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
                break;
            case self::STATUS_FAILED:
                $message = sprintf('Bold Checkout: %s synchronization failed.', $type);
                Mage::getSingleton('adminhtml/session')->addError($message);
                break;
            default:
                // Do nothing.
                break;
        }
    }
}
