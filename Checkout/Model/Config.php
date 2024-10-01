<?php

/**
 * Bold configuration service.
 */
class Bold_Checkout_Model_Config
{
    const RESOURCE = 'bold_checkout/config';
    const PATH_ENABLED = 'checkout/bold/enabled';
    const PATH_TYPE = 'checkout/bold_advanced/type';
    const PATH_ENABLED_FOR = 'checkout/bold_advanced/enabled_for';
    const PATH_IP_WHITELIST = 'checkout/bold_advanced/ip_whitelist';
    const PATH_CUSTOMER_WHITELIST = 'checkout/bold_advanced/customer_whitelist';
    const PATH_ORDERS_PERCENTAGE = 'checkout/bold_advanced/orders_percentage';
    const PATH_SECRET = 'checkout/bold/shared_secret';
    const PATH_TOKEN = 'checkout/bold/api_token';
    const PATH_API_URL = 'checkout/bold_advanced/url';
    const PATH_CHECKOUT_URL = 'checkout/bold_advanced/checkout_url';
    const PATH_PLATFORM_CONNECTOR_URL = 'checkout/bold_advanced/platform_connector_url';
    const PATH_ACCOUNT_CENTER_URL = 'checkout/bold_advanced/account_center_url';
    const PATH_INTEGRATION_IDENTITY_LINK_URL = 'checkout/bold_advanced/integration_identity_url';
    const PATH_INTEGRATION_CALLBACK_URL = 'checkout/bold_advanced/integration_callback_url';
    const PATH_WEIGHT_CONVERSION_RATE = 'checkout/bold_advanced/weight_conversion_rate';
    const PATH_WEIGHT_UNIT = 'checkout/bold_advanced/weight_unit';
    const PATH_LOG_ENABLED = 'checkout/bold_advanced/log';
    const PATH_SHOP_IDENTIFIER = 'checkout/bold/shop_identifier';
    const PATH_EXCLUDE_FOR = 'checkout/bold_advanced/exclude_for';
    const PATH_EXCLUDE_CUSTOMER_GROUPS_LIST = 'checkout/bold_advanced/exclude_customer_groups_list';
    const PATH_LIFE_ELEMENTS = 'checkout/bold_checkout_life_elements/life_elements';
    const PATH_VALIDATE_COUPON_CODES = 'checkout/bold_advanced/validate_coupon_codes';

    /**
     * Values for self::PATH_ENABLED_FOR field.
     */
    const VALUE_ENABLED_FOR_ALL = 0;
    const VALUE_ENABLED_FOR_IP = 1;
    const VALUE_ENABLED_FOR_CUSTOMER = 2;
    const VALUE_ENABLED_FOR_PERCENTAGE = 3;
    const VALUE_EXCLUDE_FOR_NONE = 0;
    const VALUE_EXCLUDE_FOR_SPECIFIED_GROUPS = 1;

    /**
     * Values for self::TYPE field.
     */
    const VALUE_TYPE_STANDARD = 0;
    const VALUE_TYPE_PARALLEL = 1;
    const VALUE_TYPE_SELF = 2;
    const VALUE_TYPE_SELF_REACT = 3;

    /**
     * Check if bold functionality enabled.
     *
     * @param int $websiteId
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function isCheckoutEnabled($websiteId)
    {
        return (bool)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_ENABLED)
            && Mage::helper('core')->isModuleOutputEnabled('Bold_Checkout');
    }

    /**
     * Show if Bold functionality is enabled for specific customers.
     *
     * @param int $websiteId
     * @return int
     */
    public function getEnabledFor($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_ENABLED_FOR);
    }

    /**
     * Get IP whitelist.
     *
     * @param int $websiteId
     * @return string[]
     */
    public function getIpWhitelist($websiteId)
    {
        $rawData = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_IP_WHITELIST);

        return $rawData ? array_filter(array_map('trim', explode(',', $rawData))) : [];
    }

    /**
     * Get Customer email whitelist.
     *
     * @param int $websiteId
     * @return string[]
     */
    public function getCustomerWhitelist($websiteId)
    {
        $rawData = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_CUSTOMER_WHITELIST);

        return $rawData ? array_filter(array_map('trim', explode(',', $rawData))) : [];
    }

    /**
     * Get Orders percentage.
     *
     * @param int $websiteId
     * @return int
     */
    public function getOrdersPercentage($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_ORDERS_PERCENTAGE);
    }

    /**
     * Get excluded customer groups.
     *
     * @param int $websiteId
     * @return array
     */
    public function getExcludedCustomerGroups($websiteId)
    {
        $excludedFor = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_EXCLUDE_FOR);
        if (!$excludedFor) {
            return [];
        }
        $rawData = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_EXCLUDE_CUSTOMER_GROUPS_LIST);
        return $rawData ? array_filter(array_map('trim', explode(',', $rawData))) : [];
    }

    /**
     * Get shared secret key (decrypted).
     *
     * @param int $websiteId
     * @return string|null
     */
    public function getSharedSecret($websiteId)
    {
        $encryptedSecret = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_SECRET);

        return Mage::helper('core')->decrypt($encryptedSecret);
    }

    /**
     * Get api token (decrypted).
     *
     * @param int $websiteId
     * @return string|null
     */
    public function getApiToken($websiteId)
    {
        $encryptedToken = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_TOKEN);

        return Mage::helper('core')->decrypt($encryptedToken);
    }

    /**
     * Get configured weight unit to grams conversion rate.
     *
     * @param int $websiteId
     * @return int
     */
    public function getWeightConversionRate($websiteId)
    {
        return (float)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_WEIGHT_CONVERSION_RATE) ?: 1000;
    }

    /**
     * Get Bold API url.
     *
     * @param int $websiteId
     * @return string
     */
    public function getApiUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_API_URL), '/');
    }

    /**
     * Get Bold Checkout url.
     *
     * @param int $websiteId
     * @return string
     */
    public function getCheckoutUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_CHECKOUT_URL), '/');
    }

    /**
     * Retrieve Bold shop identifier.
     *
     * @param int $websiteId
     * @return string|null
     */
    public function getShopIdentifier($websiteId)
    {
        return Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_SHOP_IDENTIFIER);
    }

    /**
     * Retrieve log bold requests enabled config flag.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isLogEnabled($websiteId)
    {
        return (bool)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_LOG_ENABLED);
    }

    /**
     * Retrieve self-hosted is enabled config flag.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isCheckoutTypeStandard($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_TYPE) === self::VALUE_TYPE_STANDARD;
    }

    /**
     * Retrieve self-hosted is enabled config flag.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isCheckoutTypeParallel($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_TYPE) === self::VALUE_TYPE_PARALLEL;
    }

    /**
     * Retrieve self-hosted is enabled config flag.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isCheckoutTypeSelfHosted($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_TYPE) === self::VALUE_TYPE_SELF;
    }

    /**
     * Retrieve self-hosted (react app) is enabled config flag.
     *
     * @param int $websiteId
     * @return bool
     */
    public function isCheckoutTypeSelfHostedReactApp($websiteId)
    {
        return (int)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_TYPE) === self::VALUE_TYPE_SELF_REACT;
    }

    /**
     * Get configured LiFE elements.
     *
     * @param int $websiteId
     * @return array
     * @throws Mage_Core_Exception
     */
    public function getLifeElements($websiteId)
    {
        $lifeElements = Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_LIFE_ELEMENTS);
        if (!$lifeElements) {
            return [];
        }
        /** @var Unserialize_Parser $parser */
        $serializer = Mage::helper('core/unserializeArray');
        $lifeElements = $serializer->unserialize($lifeElements);
        return is_array($lifeElements) ? $lifeElements : [];
    }

    /**
     * Retrieve Bold shop identifier.
     *
     * @param string $shopIdentifier
     * @param int $websiteId
     * @return void
     */
    public function saveShopIdentifier($shopIdentifier, $websiteId)
    {
        $websiteId
            ? Mage::getConfig()->saveConfig(self::PATH_SHOP_IDENTIFIER, $shopIdentifier, 'websites', $websiteId)
            : Mage::getConfig()->saveConfig(self::PATH_SHOP_IDENTIFIER, $shopIdentifier);
        Mage::getConfig()->cleanCache();
    }

    /**
     * Save shared secret in config.
     *
     * @param string $sharedSecret
     * @param int $websiteId
     * @return void
     */
    public function saveSharedSecret($websiteId, $sharedSecret)
    {
        $sharedSecret = Mage::helper('core')->encrypt($sharedSecret);
        $websiteId
            ? Mage::getConfig()->saveConfig(self::PATH_SECRET, $sharedSecret, 'websites', $websiteId)
            : Mage::getConfig()->saveConfig(self::PATH_SECRET, $sharedSecret);
        Mage::getConfig()->cleanCache();
    }

    /**
     * Retrieve Platform Connector URL.
     *
     * @param int $websiteId
     * @return string
     */
    public function getPlatformConnectorUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_PLATFORM_CONNECTOR_URL), '/');
    }

    /**
     * Retrieve Account Center URL.
     *
     * @param int $websiteId
     * @return string
     */
    public function getAccountCenterUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_ACCOUNT_CENTER_URL), '/');
    }

    /**
     * Retrieve Platform Connector URL.
     *
     * @param int $websiteId
     * @return string
     */
    public function getIntegrationIdentityUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_INTEGRATION_IDENTITY_LINK_URL), '/');
    }

    /**
     * Retrieve Platform Connector URL.
     *
     * @param int $websiteId
     * @return string
     */
    public function getIntegrationCallbackUrl($websiteId)
    {
        return rtrim(Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_INTEGRATION_CALLBACK_URL), '/');
    }

    /**
     * Check if coupon codes should be validated.
     *
     * @param int $websiteId
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function getValidateCouponCodes($websiteId)
    {
        return (bool)Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_VALIDATE_COUPON_CODES);
    }
}
