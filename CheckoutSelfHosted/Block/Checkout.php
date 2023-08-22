<?php

/**
 * Bold Checkout self-hosted block.
 */
class Bold_CheckoutSelfHosted_Block_Checkout extends Mage_Core_Block_Template
{
    /**
     * @var null | stdClass
     */
    private $orderData = null;

    /**
     * Get order data.
     *
     * @return stdClass
     * @throws Exception
     */
    public function getOrderData()
    {
        if ($this->orderData === null) {
            $quote = Mage::getModel('checkout/cart')->getQuote();
            try {
                $this->orderData = Bold_Checkout_Api_Bold_Orders_BoldOrder::init($quote);
            } catch (Exception $e) {
                Mage::getSingleton('checkout/session')->addError(
                    Mage::helper('core')->__(
                        'There was an error during checkout. Please contact us or try again later.'
                    )
                );
                Mage::throwException($e->getMessage());
            }
        }
        return $this->orderData;
    }

    /**
     * Get shop identifier.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getShopIdentifier()
    {
        $websiteId = Mage::app()->getWebsite()->getId();
        return Mage::getModel(Bold_Checkout_Model_Config::RESOURCE)->getShopIdentifier($websiteId);
    }

    /**
     * Get shop alias.
     *
     * @return string
     * @throws Exception
     */
    public function getShopAlias()
    {
        return $this->getOrderData()->data->initial_data->shop_name;
    }

    /**
     * Get custom domain.
     *
     * @return string
     * @throws Exception
     */
    public function getCustomDomain()
    {
        return $this->getOrderData()->data->initial_data->shop_name;
    }

    /**
     * Get shop name.
     *
     * @return string
     */
    public function getShopName()
    {
        $boldShopName = $this->getOrderData()->data->initial_data->shop_name;
        return Mage::getStoreConfig('general/store_information/name') ?: $boldShopName;
    }

    /**
     * Get return url.
     *
     * @return string
     */
    public function getReturnUrl()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
    }

    /**
     * Get login url.
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, 'customer/account/login');
    }

    /**
     * Get react app url.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getTemplateUrl()
    {
        /** @var Bold_CheckoutSelfHosted_Model_Config $selfHostedConfig */
        $selfHostedConfig = Mage::getSingleton(Bold_CheckoutSelfHosted_Model_Config::RESOURCE);
        return rtrim($selfHostedConfig->getTemplateUrl(Mage::app()->getWebsite()->getId()), '/') . '/three_page.js';
    }

    /**
     * Get store logo.
     *
     * @return string
     */
    public function getHeaderLogoUrl()
    {
        return Mage::getDesign()->getSkinUrl(Mage::getStoreConfig('design/header/logo_src'));
    }
}
