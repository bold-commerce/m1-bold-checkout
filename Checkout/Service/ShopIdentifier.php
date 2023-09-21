<?php

/**
 * Shop identifier retrieve service.
 */
class Bold_Checkout_Service_ShopIdentifier
{
    const SHOP_INFO_URL = '/shops/v1/info';

    /**
     * Get Bold shop id.
     *
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function getShopIdentifier($websiteId)
    {
        $websiteId = $websiteId ?: (int)Mage::app()->getDefaultStoreView()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $shopIdentifier = $boldConfig->getShopIdentifier($websiteId);
        if ($shopIdentifier) {
            return $shopIdentifier;
        }
        self::updateShopIdentifier($websiteId);

        return $boldConfig->getShopIdentifier($websiteId);
    }

    /**
     * Update Bold shop id.
     *
     * @param int $websiteId
     * @throws Mage_Core_Exception
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function updateShopIdentifier($websiteId) {
        $websiteId = $websiteId ?: (int)Mage::app()->getDefaultStoreView()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $apiToken = $boldConfig->getApiToken($websiteId);
        $headers = [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
            'User-Agent:' . Bold_Checkout_Service_UserAgent::getUserAgent(),
            'Bold-API-Version-Date:' . Bold_Checkout_Client::BOLD_API_VERSION_DATE,
        ];
        $url = $boldConfig->getApiUrl($websiteId). self::SHOP_INFO_URL;
        $shopInfo = json_decode(Bold_Checkout_HttpClient::call('GET', $url, null, $headers));
        if (isset($shopInfo->errors)) {
            $error = current($shopInfo->errors);
            Mage::throwException($error->message);
        }
        $shopIdentifier = $shopInfo->shop_identifier;
        $boldConfig->saveShopIdentifier($shopIdentifier, $websiteId);
    }
}
