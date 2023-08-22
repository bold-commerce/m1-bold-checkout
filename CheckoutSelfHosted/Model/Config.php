<?php

/**
 * Bold self-hosted configuration service.
 */
class Bold_CheckoutSelfHosted_Model_Config
{
    const RESOURCE = 'bold_checkout_self_hosted/config';
    const PATH_SELF_HOSTED_TEMPLATE_URL = 'checkout/bold_advanced/self_hosted_template_url';

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
}
