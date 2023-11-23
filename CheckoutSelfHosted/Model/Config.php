<?php

/**
 * Bold self-hosted configuration service.
 */
class Bold_CheckoutSelfHosted_Model_Config
{
    const RESOURCE = 'bold_checkout_self_hosted/config';
    const PATH_SELF_HOSTED_TEMPLATE_URL = 'checkout/bold_advanced/self_hosted_template_url';
    const CONFIG_PATH_TEMPLATE_URL = 'checkout/bold_advanced/template_url';
    const CONFIG_PATH_TEMPLATE_TYPE = 'checkout/bold_advanced/template_type';
    const CONFIG_PATH_TEMPLATE_FILE = 'checkout/bold_advanced/template_file';

    /**
     * Get react app template url.
     *
     * @param int $websiteId
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getTemplateUrl($websiteId)
    {
        return Mage::app()->getWebsite($websiteId)->getConfig(self::PATH_SELF_HOSTED_TEMPLATE_URL);
    }

    /**
     * Retrieve configured checkout template url.
     *
     * @param int $websiteId
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getCheckoutTemplateUrl($websiteId)
    {
        return Mage::app()->getWebsite($websiteId)->getConfig(self::CONFIG_PATH_TEMPLATE_URL);
    }

    /**
     * Retrieve configured checkout template type (one-page, three-pages).
     *
     * @param int $websiteId
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getCheckoutTemplateType($websiteId)
    {
        return Mage::app()->getWebsite($websiteId)->getConfig(self::CONFIG_PATH_TEMPLATE_TYPE);
    }

    /**
     * Retrieve custom checkout template file path save in configuration.
     *
     * @param int $websiteId
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getCheckoutTemplateFile($websiteId)
    {
        return Mage::app()->getWebsite($websiteId)->getConfig(self::CONFIG_PATH_TEMPLATE_FILE);
    }

    /**
     * Retrieve default one-page or three-pages template file url.
     *
     * @param int $websiteId
     * @return string
     */
    public function getViewFileUrl($websiteId)
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'bold/checkout/template/'
            . $this->getCheckoutTemplateType($websiteId) . '.js';
    }
}
