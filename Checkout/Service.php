<?php

/**
 * Perform requests to bold service.
 */
class Bold_Checkout_Service
{
    const BOLD_API_VERSION_DATE = "2022-10-14";

    /**
     * Perform http request.
     *
     * @param string $method
     * @param string $url
     * @param int $websiteId
     * @param string|null $data
     * @param bool $removeFlowId
     * @return string
     * @throws Mage_Core_Exception
     */
    public static function call($method, $url, $websiteId, $data = null, $removeFlowId = false)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $shopId = Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId);
        $headers = [
            'Authorization: Bearer ' . $config->getApiToken($websiteId),
            'Content-Type: application/json',
            'User-Agent:' . Bold_Checkout_Service_UserAgent::getUserAgent(),
            'Bold-API-Version-Date:' . self::BOLD_API_VERSION_DATE,
        ];
        $url = $config->getApiUrl($websiteId) . '/' . ltrim(str_replace('{{shopId}}', $shopId, $url), '/');
        return Bold_Checkout_HttpClient::call($method, $url, $data, $headers, $removeFlowId);
    }
}
