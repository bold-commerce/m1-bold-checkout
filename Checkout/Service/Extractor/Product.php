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
     * @param int $websiteId
     * @return Mage_Catalog_Model_Product[]
     * @throws Zend_Locale_Exception
     */
    public static function extract(array $products, $websiteId)
    {
        $result = [];
        foreach ($products as $product) {
            $result[] = self::extractProduct($product, $websiteId);
        }

        return $result;
    }

    /**
     * Extract product entity data into array.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @return array
     * @throws Zend_Locale_Exception
     */
    private static function extractProduct(Mage_Catalog_Model_Product $product, $websiteId)
    {
        $categoryCollection = $product->getCategoryCollection();
        $categories = $categoryCollection->addAttributeToSelect(['name', 'image'])->getItems();
        $stockItem = $product->getStockItem();
        if (!$stockItem) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $product->setStockItem($stockItem);
        }
        $updatedAt = strtotime($product->getUpdatedAt()) > 0 ? $product->getUpdatedAt() : now();
        $createdAt = strtotime($product->getCreatedAt()) > 0 ? $product->getCreatedAt() : now();

        return [
            'platform_id' => (string)$product->getId(),
            'platform_updated_at' => Mage::getSingleton('core/date')->date('c', strtotime($updatedAt)),
            'platform_created_at' => Mage::getSingleton('core/date')->date('c', strtotime($createdAt)),
            'name' => (string)$product->getName(),
            'published' => $product->isVisibleInCatalog(),
            'tags' => (string)$product->getTags(),
            'tax_code' => (int)$product->getTaxClassId() === 6 ? 'non-taxable' : 'taxable',
            'type' => 'simple',
            'url' => $product->getProductUrl(),
            'vendor' => (string)$product->getManufacturer(),
            'description' => (string)$product->getDescription(),
            'handle' => (string)$product->getSku(),
            'inventory_quantity' => (int)$stockItem->getQty(),
            'inventory_tracking_entity' => 'product',
            'inventory_tracking_service' => 'platform',
            'categories' => Bold_Checkout_Service_Extractor_Category::extract(array_values($categories)),
            'options' => [],
            'variants' => [self::extractProductVariant($product, $websiteId)],
            'images' => self::extractProductImages(
                $product->getMediaGallery() ? $product->getMediaGallery()['images'] : []
            ),
            'localized_descriptions' => Bold_Checkout_Service_Extractor_Product_Attributes::extractLocalizedValues(
                $product,
                'description'
            ),
            'localized_names' => Bold_Checkout_Service_Extractor_Product_Attributes::extractLocalizedValues(
                $product,
                'name'
            ),
        ];
    }

    /**
     * Extract product variant data.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @return array
     * @throws Zend_Locale_Exception
     */
    private static function extractProductVariant(Mage_Catalog_Model_Product $product, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $imageUrl = '';
        $mediaGallery = $product->getMediaGallery() ?: ['images' => []];
        foreach ($mediaGallery['images'] as $galleryImage) {
            if ($galleryImage['file']) {
                $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)
                    . 'catalog/product' . $galleryImage['file'];
                break;
            }
        }
        $productWeightInGrams = (float)$product->getWeight() * $boldConfig->getWeightConversionRate($websiteId);
        return [
            'platform_id' => (string)$product->getId(),
            'option_values' => [],
            'allow_backorder' => (int)$product->getStockItem()->getBackorders() !== 0,
            'compare_at_price' => (string)Mage::app()->getStore()->roundPrice($product->getPrice()),
            'cost' => (string)Mage::app()->getStore()->roundPrice($product->getPrice()) ?: '0.0',
            'price' => (string)Mage::app()->getStore()->roundPrice($product->getPrice()) ?: '0.0',
            'grams' => (int)$productWeightInGrams,
            'image_url' => $imageUrl,
            'inventory_quantity' => (int)$product->getStockItem()->getQty(),
            'inventory_tracking_entity' => 'product',
            'inventory_tracking_service' => 'platform',
            'localized_names' => Bold_Checkout_Service_Extractor_Product_Attributes::extractLocalizedValues(
                $product,
                'name'
            ),
            'name' => 'Default Title',
            'require_shipping' => !$product->isVirtual(),
            'sku' => (string)$product->getSku(),
            'tax_code' => (int)$product->getTaxClassId() === 6 ? 'non-taxable' : 'taxable',
            'tax_exempt' => (int)$product->getTaxClassId() === 6 ? true : null,
            'weight' => (string)$product->getWeight() ?: '0.0',
            'weight_unit' => $boldConfig->getWeightUnit($websiteId),
        ];
    }

    /**
     * Extract product images data.
     *
     * @param array $images
     * @return array
     */
    private static function extractProductImages(array $images)
    {
        $result = [];
        foreach ($images as $image) {
            $result[] = [
                'platform_id' => (string)$image['value_id'],
                'name' => (string)$image['label'],
                'src' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $image['file'],
                'position' => (int)$image['position'],
            ];
        }

        return $result;
    }
}
