<?php

/**
 * Register overrides on bold side service.
 */
class Bold_Checkout_Api_Bold_Overrides
{
    const OVERRIDES_URL = '/checkout/shop/{{shopId}}/overrides';
    const OVERRIDES_TO_REGISTER = [
        'shipping' => 'bold/v1/shops/%s/overrides/shipping',
        'inventory' => 'bold/v1/shops/%s/overrides/inventory',
        'tax' => 'bold/v1/shops/%s/overrides/tax',
        'address_validate' => 'bold/v1/shops/%s/overrides/address_validate',
        'discount' => 'bold/v1/shops/%s/overrides/discount',
    ];

    /**
     * Register predefined overrides in bold app.
     *
     * @param int $websiteId
     * @return void
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     * @throws Exception
     */
    public static function register($websiteId)
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)) {
            return;
        }
        $sharedSecret = $boldConfig->getSharedSecret($websiteId);
        if (!$sharedSecret) {
            Mage::throwException(Mage::helper('core')->__('Shared key should be configured.'));
        }
        $result = json_decode(
            Bold_Checkout_Service::call('GET', self::OVERRIDES_URL, $websiteId),
            true
        );
        $error = isset($result['errors']['message']) ? $result['errors']['message'] : null;
        if ($error) {
            Mage::throwException($error);
        }
        $existingOverrides = isset($result['data']) ? $result['data'] : [];
        foreach (self::OVERRIDES_TO_REGISTER as $type => $url) {
            self::registerOverride($type, $url, $sharedSecret, $websiteId, $existingOverrides);
        }
    }

    /**
     * Register override for given type.
     *
     * @param string $type
     * @param string $url
     * @param string $sharedSecret
     * @param int $websiteId
     * @param array $existingOverrides
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function registerOverride($type, $url, $sharedSecret, $websiteId, array $existingOverrides)
    {
        $website = Mage::app()->getWebsite($websiteId);
        $shopIdentifier = Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId);
        $url = $website->getDefaultStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
            . sprintf($url, $shopIdentifier);
        $body = [
            'override_type' => $type,
            'url' => $url,
            'shared_secret' => $sharedSecret,
        ];
        foreach ($existingOverrides as $existingOverride) {
            if ($existingOverride['override_type'] === $type && $existingOverride['url'] === $url) {
                return;
            }
            if ($existingOverride['override_type'] === $type && $existingOverride['url'] !== $url) {
                $result = json_decode(
                    Bold_Checkout_Service::call(
                        'PATCH',
                        self::OVERRIDES_URL . '/' . $existingOverride['public_id'],
                        $websiteId,
                        json_encode($body)
                    )
                );
                if (!isset($result->data->public_id)) {
                    Mage::throwException(Mage::helper('core')->__('Cannot update override "%s"', $type));
                }
                return;
            }
        }
        $result = json_decode(Bold_Checkout_Service::call('POST', self::OVERRIDES_URL, $websiteId, json_encode($body)));
        if (!isset($result->data->public_id)) {
            Mage::throwException(Mage::helper('core')->__('Cannot register override "%s"', $type));
        }
    }
}
