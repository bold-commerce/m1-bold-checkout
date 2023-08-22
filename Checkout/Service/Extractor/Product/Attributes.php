<?php

/**
 * Service for getting product attribute values for all stores.
 */
class Bold_Checkout_Service_Extractor_Product_Attributes
{
    /**
     * @var array
     */
    private static $storeCodeCache = [];

    /**
     * @var array
     */
    private static $attributeCache = [];

    /**
     * Retrieve localized product attribute values.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode
     * @return stdClass
     * @throws Zend_Locale_Exception
     */
    public static function extractLocalizedValues(Mage_Catalog_Model_Product $product, $attributeCode)
    {
        $result = new stdClass();
        $defaultValues = self::getDefaultValues($product, $attributeCode);
        $actualValues = self::getActualValues($product, $attributeCode);
        $values = array_merge($defaultValues, $actualValues);
        if (!$values) {
            return $result;
        }
        foreach ($values as $language => $value) {
            $result->$language = (string)$value;
        }

        return $result;
    }

    /**
     * Load attribute model.
     *
     * @param string $attributeCode
     * @return Mage_Catalog_Model_Resource_Eav_Attribute
     */
    private static function getAttribute($attributeCode)
    {
        if (!isset(self::$attributeCache[$attributeCode])) {
            self::$attributeCache[$attributeCode] = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
        }

        return self::$attributeCache[$attributeCode];
    }

    /**
     * Retrieve store language codes.
     *
     * @param array $storeIds
     * @return array
     * @throws Zend_Locale_Exception
     */
    private static function getStoreLanguages(array $storeIds)
    {
        if (empty(self::$storeCodeCache)) {
            $storeCollection = Mage::getSingleton('core/store')->getCollection();
            foreach ($storeCollection as $store) {
                $storeCode = $store->getCode();
                $localeCode = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeCode);
                // phpcs:ignore Bold.Stdlib.DateTime.Overcomplicated
                $languageCode = (new Zend_Locale($localeCode))->getLanguage();
                self::$storeCodeCache[$store->getId()] = $languageCode;
            }
        }

        return array_intersect_key(self::$storeCodeCache, array_fill_keys($storeIds, null));
    }

    /**
     * Get attribute default values.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode
     * @return array
     * @throws Zend_Locale_Exception
     */
    private static function getDefaultValues(Mage_Catalog_Model_Product $product, $attributeCode)
    {
        $storeIds = $product->getStoreIds();
        $storeLanguages = self::getStoreLanguages($storeIds);
        $defaultAttributeValue = $product->getData($attributeCode);

        return array_fill_keys($storeLanguages, $defaultAttributeValue);
    }

    /**
     * Get attribute values for each store.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode
     * @return array
     * @throws Zend_Locale_Exception
     */
    private static function getActualValues(Mage_Catalog_Model_Product $product, $attributeCode)
    {
        $storeIds = $product->getStoreIds();
        $storeLanguages = self::getStoreLanguages($storeIds);
        $productId = $product->getId();
        $attribute = self::getAttribute($attributeCode);
        $attributeId = $attribute->getId();
        $attributeTable = $attribute->getBackendTable();
        $resource = Mage::getSingleton('core/resource');
        /** @var Magento_Db_Adapter_Pdo_Mysql $connection */
        $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
        // phpcs:disable MEQP1.Classes.ResourceModel.OutsideOfResourceModel
        $select = $connection->select()
            ->from(
                ['attribute' => $attributeTable],
                ['value', 'store_id']
            )
            ->where(
                'attribute.entity_id = ?', $productId
            )
            ->where(
                'attribute.attribute_id = ?', $attributeId
            )
            ->where(
                'store.store_id IN (?)', $storeIds
            )->join(
                ['store' => $resource->getTableName('core_store')],
                'store.store_id = attribute.store_id',
                []
            );
        // phpcs:enable MEQP1.Classes.ResourceModel.OutsideOfResourceModel
        // phpcs:ignore MEQP1.Performance.InefficientMethods.FoundFetchAll
        $storeValues = $connection->fetchAll($select);
        $languageValues = [];
        foreach ($storeValues as $storeValue) {
            $storeId = $storeValue['store_id'];
            $value = $storeValue['value'];
            $storeLanguage = $storeLanguages[$storeId];
            $languageValues[$storeLanguage] = $value;
        }

        return $languageValues;
    }
}
