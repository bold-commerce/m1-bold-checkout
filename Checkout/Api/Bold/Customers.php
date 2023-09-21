<?php

/**
 * Update customer information on bold side service.
 */
class Bold_Checkout_Api_Bold_Customers
{
    const CUSTOMER_SAVE_URL = '/{{shopId}}/webhooks/customers/saved';
    CONST CUSTOMER_DELETE_URL = '/{{shopId}}/webhooks/customers/deleted';

    /**
     * Update customer on bold side.
     *
     * @param array $queryParameters
     * @param int $websiteId
     * @return stdClass()
     * @throws Mage_Core_Exception
     */
    public static function update(array $queryParameters, $websiteId)
    {
        $customers = Bold_Checkout_Model_CustomerListBuilder::build($queryParameters);
        $body = new stdClass();
        $body->items = $customers->items;
        return json_decode(
            Bold_Checkout_PlatformClient::call(
                Zend_Http_Client::POST,
                self::CUSTOMER_SAVE_URL,
                $websiteId,
                json_encode($body)
            )
        );
    }

    /**
     * Send delete customer request to bold.
     *
     * @param array $customerIds
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function deleted(array $customerIds, $websiteId)
    {
        return json_decode(
            Bold_Checkout_PlatformClient::call(
                Zend_Http_Client::POST,
                self::CUSTOMER_DELETE_URL,
                $websiteId,
                json_encode(['ids' => $customerIds])
            )
        );
    }
}
