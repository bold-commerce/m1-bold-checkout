<?php

/**
 * Perform requests to bold storefront service.
 */
class Bold_Checkout_StorefrontService
{
    const URL = 'https://api.boldcommerce.com/checkout/storefront/';

    /**
     * Perform http request.
     *
     * @param string $method
     * @param string $path
     * @param array $body
     * @return mixed
     * @throws Mage_Core_Exception
     */
    public static function call($method, $path, array $body = [])
    {
        $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            Mage::throwException('Bold Checkout data is not set.');
        }
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $shopId = Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId);
        $url = self::URL . $shopId . '/' . $boldCheckoutData->data->public_order_id . '/' . $path;
        $headers = [
            'Authorization: Bearer ' . $boldCheckoutData->data->jwt_token,
            'Content-Type: application/json',
            'User-Agent:' . Bold_Checkout_Service_UserAgent::getUserAgent(),
            'Bold-API-Version-Date:' . Bold_Checkout_Client::BOLD_API_VERSION_DATE,
        ];
        $result = json_decode(
            Bold_Checkout_HttpClient::call(
                $method,
                $url,
                $body ? json_encode($body) : null,
                $headers
            )
        );
        if (isset($result->data->application_state)) {
            $boldCheckoutData->data->application_state = $result->data->application_state;
            Mage::getSingleton('checkout/session')->setBoldCheckoutData($boldCheckoutData);
        }
        return $result;
    }
}
