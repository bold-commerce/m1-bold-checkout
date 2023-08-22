<?php

/**
 * Update category data on bold side service.
 */
class Bold_Checkout_Api_Bold_Categories
{
    /**
     * Update category data on bold side after one has been saved.
     *
     * @param Mage_Catalog_Model_Category $category
     * @param int $websiteId
     * @return string
     * @throws Exception
     */
    public static function updated(Mage_Catalog_Model_Category $category, $websiteId)
    {
        $body = [
            'data' => [
                'category' => current(Bold_Checkout_Service_Extractor_Category::extract([$category])),
            ],
        ];

        return Bold_Checkout_Service::call(
            'POST',
            '/products/v1/shops/{{shopId}}/platforms/custom/webhooks/categories/saved',
            $websiteId,
            json_encode($body)
        );
    }

    /**
     * Update category data on bold side after category has been saved.
     *
     * @param int $id
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function deleted($id, $websiteId)
    {
        $body = [
            'data' => [
                'category' => [
                    'platform_id' => (string)$id,
                    'platform_deleted_at' => Mage::getSingleton('core/date')->date('c'),
                ],
            ],
        ];

        return Bold_Checkout_Service::call(
            'POST',
            '/products/v1/shops/{{shopId}}/platforms/custom/webhooks/categories/deleted',
            $websiteId,
            json_encode($body)
        );
    }
}
