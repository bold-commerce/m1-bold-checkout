<?php

/**
 * Load and add media gallery data to product service.
 */
class Bold_Checkout_Service_MediaGalleryData
{
    /**
     * Add media gallery to product.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return void
     */
    public static function addToProduct(Mage_Catalog_Model_Product $product)
    {
        $images = isset($product->getMediaGallery()['images']) ? $product->getMediaGallery()['images'] : [];
        if ($images) {
            return;
        }
        $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
        $mediaGallery = $attributes['media_gallery'];
        $backend = $mediaGallery->getBackend();
        $backend->afterLoad($product);
        if ($product->getMediaGallery()['images']) {
            return;
        }
        /** @var Mage_Catalog_Model_Product_Type_Configurable $configurableType */
        $configurableType = Mage::getSingleton('catalog/product_type_configurable');
        $configurableProductIds = $configurableType->getParentIdsByChild($product->getId());
        if (!$configurableProductIds) {
            return;
        }
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();
        $configurableProducts = $collection->addFieldToFilter(
            'entity_id',
            ['in' => $configurableProductIds]
        )->getItems();
        foreach ($configurableProducts as $configurableProduct) {
            $backend->afterLoad($configurableProduct);
            if ($configurableProduct->getMediaGallery()['images']) {
                $product->setMediaGallery($configurableProduct->getMediaGallery());
                return;
            }
        }
    }

    /**
     * Add media gallery to products.
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract $products abstract collection used for backward compatibility.
     * @return void
     */
    public static function addToProducts(Mage_Eav_Model_Entity_Collection_Abstract $products)
    {
        $loadedIds = $products->load()->getLoadedIds();
        $configurableIds = Bold_Checkout_Model_Resource_GetProductParentIdsByChildrenIds::getParentIds($loadedIds);
        $resourceModel = Mage::getResourceModel('catalog/product_attribute_backend_media');
        $mediaData = method_exists($resourceModel, 'loadGallerySet')
            ? $resourceModel->loadGallerySet(
                array_merge($configurableIds, $loadedIds),
                $products->getStoreId()
            )
            : Bold_Checkout_Model_Resource_GallerySet::loadGallerySet(
                array_merge($configurableIds, $loadedIds),
                $products->getStoreId()
            );

        foreach ($products->getItems() as $product) {
            $mediaProductIds = self::getMediaProductIds($product->getId(), $configurableIds);
            $mediaGalleryData = self::getMediaGalleryForProduct($mediaData, $mediaProductIds);
            $product->setMediaGallery($mediaGalleryData);
        }
    }

    /**
     * Create array of Product ids suitable for Media Gallery data binding.
     *
     * @param int $productId
     * @param array $configurableIds
     * @return array
     */
    private static function getMediaProductIds($productId, array $configurableIds)
    {
        $productIds = [$productId];
        array_key_exists($productId, $configurableIds)
        && array_push($productIds, ...$configurableIds[$productId]);

        return $productIds;
    }

    /**
     * Get Media Gallery data for Product.
     *
     * @param array $mediaData
     * @param array $mediaProductIds
     * @return array
     */
    private static function getMediaGalleryForProduct(array $mediaData, array $mediaProductIds)
    {
        $media = [
            'images' => [],
            'values' => [],
        ];
        $localAttributes = ['label', 'position', 'disabled'];
        foreach ($mediaData as $image) {
            if (!in_array($image['product_id'], $mediaProductIds)) {
                continue;
            }
            foreach ($localAttributes as $localAttribute) {
                if (null === $image[$localAttribute]) {
                    $image[$localAttribute] = isset($image[$localAttribute . '_default'])
                        ? $image[$localAttribute . '_default']
                        : '';
                }
            }
            $image['value_id'] = (string)$image['value_id'];
            $media['images'][] = $image;
        }

        return $media;
    }
}
