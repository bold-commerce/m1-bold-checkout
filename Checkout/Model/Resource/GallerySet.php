<?php

/**
 * \Mage_Catalog_Model_Resource_Product_Attribute_Backend_Media::loadGallerySet emulation for obsolete code versions.
 */
class Bold_Checkout_Model_Resource_GallerySet
{
    const MAIN_TABLE = 'catalog_product_entity_media_gallery';
    const GALLERY_VALUE_TABLE = 'catalog_product_entity_media_gallery_value';

    /**
     * Get media gallery set for given product IDs
     *
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public static function loadGallerySet(array $productIds, $storeId)
    {
        $attribute = Mage::getModel('eav/entity_attribute')
            ->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'media_gallery');
        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        $select = self::getLoadGallerySelect($resource, $connection, $productIds, $storeId, $attribute->getId());

        //phpcs:ignore MEQP1.Performance.InefficientMethods.FoundFetchAll
        return $connection->fetchAll($select);
    }

    /**
     * Get select to retrieve media gallery images for given product IDs.
     *
     * @param Mage_Core_Model_Resource $resource
     * @param Varien_Db_Adapter_Pdo_Mysql $connection
     * @param array $productIds
     * @param int $storeId
     * @param int $attributeId
     * @return Varien_Db_Select
     */
    private static function getLoadGallerySelect(
        Mage_Core_Model_Resource $resource,
        Varien_Db_Adapter_Pdo_Mysql $connection,
        array $productIds,
        $storeId,
        $attributeId
    ) {
        $positionCheckSql = new Zend_Db_Expr(
            sprintf(
                "IF(%s, %s, %s)",
                'value.position IS NULL',
                'default_value.position',
                'value.position'
            )
        );
        return $connection->select()->from(
            ['main' => $resource->getTableName(self::MAIN_TABLE)],
            ['value_id', 'value AS file', 'product_id' => 'entity_id']
        )->joinLeft(
            ['value' => $resource->getTableName(self::GALLERY_VALUE_TABLE)],
            $connection->quoteInto('main.value_id = value.value_id AND value.store_id = ?', (int)$storeId),
            ['label', 'position', 'disabled']
        )->joinLeft(
            ['default_value' => $resource->getTableName(self::GALLERY_VALUE_TABLE)],
            'main.value_id = default_value.value_id AND default_value.store_id = 0',
            [
                'label_default' => 'label',
                'position_default' => 'position',
                'disabled_default' => 'disabled',
            ]
        )->where('main.attribute_id = ?', $attributeId)
            ->where('main.entity_id in (?)', $productIds)
            ->order($positionCheckSql . ' ' . Varien_Db_Select::SQL_ASC);
    }
}
