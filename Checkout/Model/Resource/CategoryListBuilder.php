<?php

/**
 * Build category collection for categories export to bold.
 */
class Bold_Checkout_Model_Resource_CategoryListBuilder
{
    /**
     * Prepare category collection.
     *
     * @param int $limit
     * @param int $cursor
     * @param int $websiteId
     * @return Mage_Catalog_Model_Resource_Category_Collection
     * @throws Exception
     */
    public static function build($limit, $cursor, $websiteId)
    {
        /** @var Mage_Catalog_Model_Resource_Category_Collection $list */
        $list = Mage::getModel('catalog/category')->getCollection();
        $list->addAttributeToSelect(['name', 'image', 'updated_at']);
        $store = Mage::app()->getWebsite($websiteId)->getDefaultStore();
        $rootCategoryId = $store->getRootCategoryId();
        $list->addAttributeToFilter('path', ['like' => "1/{$rootCategoryId}/%"]);
        $list->setStoreId($store->getId());
        $list->setPageSize($limit);
        $list->setCurPage($cursor);
        return $list;
    }
}
