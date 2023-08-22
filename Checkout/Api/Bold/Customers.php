<?php

/**
 * Update customer information on bold side service.
 */
class Bold_Checkout_Api_Bold_Customers
{
    /**
     * Update customer on bold side.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param int $websiteId
     * @return string
     * @throws Exception
     */
    public static function updated(Mage_Customer_Model_Customer $customer, $websiteId)
    {
        $body = [
            'data' => [
                'customer' => current(Bold_Checkout_Service_Extractor_Customer::extract([$customer])),
            ],
        ];
        $url = '/customers/v1/shops/{{shopId}}/platforms/custom/webhooks/customers/saved';

        return Bold_Checkout_Service::call('POST', $url, $websiteId, json_encode($body));
    }

    /**
     * Send delete customer request to bold.
     *
     * @param int $customerId
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function deleted($customerId, $websiteId)
    {
        $body = [
            'data' => [
                'customer' => [
                    'platform_id' => (string)$customerId,
                    'platform_deleted_at' => Mage::getSingleton('core/date')->date('c'),
                ],
            ],
        ];

        return Bold_Checkout_Service::call(
            'POST',
            '/customers/v1/shops/{{shopId}}/platforms/custom/webhooks/customers/deleted',
            $websiteId,
            json_encode($body)
        );
    }
}
