<?php

/**
 * Product entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Product
{
    /**
     * Extract products data.
     *
     * @param array $products
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(array $products)
    {
        $result = [];
        foreach ($products as $product) {
            $result[] = self::extractProduct($product);
        }
        return $result;
    }

    /**
     * Extract product entity data into array.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function extractProduct(Mage_Catalog_Model_Product $product)
    {
        $categoryCollection = $product->getCategoryCollection();
        $categories = $categoryCollection->addAttributeToSelect('position')->getItems();
        $categoryLinks = [];
        $customAttributes = [];
        foreach ($product->getAttributes(null, true) as $attribute) {
            if ($attribute->getBackendType() === Mage_Eav_Model_Entity_Attribute_Abstract::TYPE_STATIC) {
                continue;
            }
            if ($attribute->getAttributeCode() === 'media_gallery') {
                continue;
            }
            $customAttributes[] = [
                'attribute_code' => $attribute->getAttributeCode(),
                'value' => $product->getData($attribute->getAttributeCode()),
            ];
        }
        foreach ($categories as $category) {
            $categoryLinks[] = [
                'position' => (int)$category->getPosition(),
                'category_id' => (string)$category->getId(),
            ];
        }
        $mediaGalleryEntries = [];
        if (!$product->getMediaGalleryImages()) {
            Bold_Checkout_Service_MediaGalleryData::addToProduct($product);
        }
        $images = $product->getMediaGalleryImages() ?: [];
        foreach ($images as $image) {
            $mediaGalleryEntries[] = [
                'id' => (int)$image->getId(),
                'media_type' => $image->getMediaType() ?: 'image',
                'label' => (string)$image->getLabel(),
                'position' => (int)$image->getPosition(),
                'disabled' => (bool)$image->getDisabled(),
                'types' => $image->getTypes() ?: ['image', 'small_image', 'thumbnail'],
                'file' => $image->getFile(),
            ];
        }
        $createdAt = strtotime($product->getCreatedAt()) > 0 ? $product->getCreatedAt() : now();
        $updatedAt = strtotime($product->getUpdatedAt()) > 0 ? $product->getUpdatedAt() : now();
        return [
            'id' => (int)$product->getEntityId(),
            'sku' => $product->getData('sku'),
            'name' => $product->getName(),
            'attribute_set_id' => (int)$product->getAttributeSetId(),
            'price' => (float)$product->getPrice(),
            'status' => (int)$product->getStatus(),
            'visibility' => (int)$product->getVisibility(),
            'type_id' => $product->getTypeId(),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'extension_attributes' => [
                'website_ids' => array_map('intval', $product->getWebsiteIds()),
                'category_links' => $categoryLinks,
                'is_virtual' => (bool)$product->getIsVirtual(),
            ],
            'product_links' => [],
            'options' => self::extractOptions($product),
            'media_gallery_entries' => $mediaGalleryEntries,
            'tier_prices' => $product->getFormatedTierPrice(),
            'custom_attributes' => $customAttributes,
        ];
    }

    /**
     * Extract product options.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function extractOptions(Mage_Catalog_Model_Product $product)
    {
        $options = [];
        /** @var Mage_Catalog_Model_Product_Option $option */
        foreach ($product->getOptions() as $option) {
            $values = [];
            foreach ($option->getValues() as $value) {
                $values[] = [
                    'title' => html_entity_decode($value->getTitle()),
                    'sort_order' => (int)$value->getSortOrder(),
                    'price' => Mage::app()->getStore()->roundPrice($value->getPrice(true)),
                    'price_type' => $value->getPriceType(),
                    'option_type_id' => (int)$value->getOptionTypeId(),
                ];
            }
            $options[] = [
                'product_sku' => $product->getData('sku'),
                'option_id' => (int)$option->getId(),
                'title' => html_entity_decode($option->getTitle()),
                'type' => $option->getType(),
                'sort_order' => (int)$option->getSortOrder(),
                'is_require' => (bool)$option->getIsRequire(),
                'price' => Mage::app()->getStore()->roundPrice($option->getPrice(true)),
                'price_type' => $option->getPriceType() ?: 'fixed',
                'max_characters' => (int)$option->getMaxCharacters(),
                'image_size_x' => (int)$option->getImageSizeX(),
                'image_size_y' => (int)$option->getImageSizeY(),
                'values' => $values
            ];
        }
        return $options;
    }
}
