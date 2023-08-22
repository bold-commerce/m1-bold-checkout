<?php

/**
 * Build product collection for product export to bold.
 */
class Bold_Checkout_Model_Resource_ProductListBuilder
{
    /**
     * Prepare product collection.
     *
     * @param int $limit
     * @param int $cursor
     * @param int $websiteId
     * @return Mage_Catalog_Model_Resource_Product_Collection
     * @throws Exception
     */
    public static function build($limit, $cursor, $websiteId)
    {
        /** @var Mage_Catalog_Model_Resource_Product_Collection $list */
        $list = Mage::getModel('catalog/product')->getCollection();
        $list->addWebsiteFilter([$websiteId]);
        $list->setStoreId(Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId());
        $list->addFieldToFilter(
            'type_id',
            [
                'nin' => [
                    Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                    Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                    Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                ]
            ]
        );
        $list->addAttributeToSelect(
            [
                'name',
                'sku',
                'description',
                'short_description',
                'updated_at',
                'created_at',
                'special_price',
                'regular_price',
                'tax_class_id',
                'cost',
                'price',
                'weight',
                'manufacturer',
                'status',
            ]
        );
        $list->setFlag('require_stock_items', true);
        $list->setPageSize($limit);
        $list->setCurPage($cursor);

        return $list;
    }
}
