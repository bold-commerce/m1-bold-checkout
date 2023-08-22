<?php

/**
 * Product synchronization service.
 */
class Bold_Checkout_Api_Bold_Products
{
    /**
     * Update product data on bold side.
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @return string
     * @throws Exception
     */
    public static function updated(Mage_Catalog_Model_Product $product, $websiteId)
    {
        $product = current(Bold_Checkout_Service_Extractor_Product::extract([$product], $websiteId));
        $body = [
            'data' => [
                'product' => $product,
            ],
        ];
        return Bold_Checkout_Service::call(
            'POST',
            '/products/v1/shops/{{shopId}}/platforms/custom/webhooks/products/saved',
            $websiteId,
            json_encode($body)
        );
    }

    /**
     * Delete product on bold side.
     *
     * @param int $productId
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function deleted($productId, $websiteId)
    {
        $body = [
            'data' => [
                'product' => [
                    'platform_id' => (string)$productId,
                    'platform_deleted_at' => Mage::getSingleton('core/date')->date('c'),
                ],
            ],
        ];

        return Bold_Checkout_Service::call(
            'POST',
            '/products/v1/shops/{{shopId}}/platforms/custom/webhooks/products/deleted',
            $websiteId,
            json_encode($body)
        );
    }
}
