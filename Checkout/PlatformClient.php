<?php

/**
 * Perform requests to bold service.
 */
class Bold_Checkout_PlatformClient
{
    /**
     * Perform http request.
     *
     * @param string $method
     * @param string $url
     * @param int $websiteId
     * @param string|null $data
     * @return string
     */
    public static function call($method, $url, $websiteId, $data = null)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $headers = self::getHeaders($config, $websiteId);
        $url = self::getUrl($config, $websiteId, $url);
        return Bold_Checkout_HttpClient::call($method, $url, $websiteId, $data, $headers);
    }

    /**
     * Build platform connector headers.
     *
     * @param Bold_Checkout_Model_Config $config
     * @param int $websiteId
     * @return string[]
     */
    private static function getHeaders(Bold_Checkout_Model_Config $config, $websiteId)
    {
        $secret = $config->getSharedSecret($websiteId);
        date_default_timezone_set('UTC');
        $timestamp = date(DateTime::RFC3339);
        $hmac = base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
        return [
            'X-HMAC-Timestamp: ' . $timestamp,
            'X-HMAC: ' . $hmac,
            'Content-Type: application/json',
        ];
    }

    /**
     * Build platform connector url.
     *
     * @param Bold_Checkout_Model_Config $config
     * @param int $websiteId
     * @param string $url
     * @return string
     */
    private static function getUrl(Bold_Checkout_Model_Config $config, $websiteId, $url)
    {
        $shopId = $config->getShopIdentifier($websiteId);
        return $config->getPlatformConnectorUrl($websiteId) . str_replace('{{shopId}}', $shopId, $url);
    }
}
