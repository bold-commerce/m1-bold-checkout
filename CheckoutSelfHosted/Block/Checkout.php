<?php

/**
 * Bold Checkout self-hosted block.
 */
class Bold_CheckoutSelfHosted_Block_Checkout extends Mage_Core_Block_Template
{
    const UPLOAD_DIR = 'bold/checkout/template';

    /**
     * @var null | stdClass
     */
    private $orderData = null;

    /**
     * Get Bold order data.
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
     * Retrieve public order id from bold checkout data.
     *
     * @return string
     */
    public function getPublicOrderId()
    {
        $session = Mage::getSingleton('checkout/session');
        return isset($session->getBoldCheckoutData()['data']['public_order_id'])
            ? $session->getBoldCheckoutData()['data']['public_order_id']
            : '';
    }

    /**
     * Retrieve template script URL.
     *
     * @return string
     */
    public function getCheckoutTemplateScriptUrl()
    {
        $checkoutSession = Mage::getSingleton('checkout/session');
        $websiteId = (int)$checkoutSession->getQuote()->getStore()->getWebsiteId();
        /** @var Bold_CheckoutSelfHosted_Model_Config $config */
        $config = Mage::getSingleton(Bold_CheckoutSelfHosted_Model_Config::RESOURCE);
        $templateUrl = $config->getCheckoutTemplateUrl($websiteId);
        $templateType = $config->getCheckoutTemplateType($websiteId);
        if ($templateUrl) {
            return rtrim($templateUrl, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $templateType . '.js';
        }
        $templateFile = $config->getCheckoutTemplateFile($websiteId);
        if ($templateFile) {
            $mediaUrl = $checkoutSession->getQuote()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            return $mediaUrl . self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $templateFile;
        }
        return $config->getViewFileUrl($websiteId);
    }

    /**
     * Get shop identifier.
     *
     * @return string
     */
    public function getShopIdentifier()
    {
        $websiteId = Mage::app()->getWebsite()->getId();
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        return $config->getShopIdentifier($websiteId);
    }

    /**
     * Get shop alias.
     *
     * @return string
     */
    public function getShopAlias()
    {
        try {
            return $this->getOrderData()->data->initial_data->shop_name;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get custom domain.
     *
     * @return string
     */
    public function getCustomDomain()
    {
        try {
            return $this->getOrderData()->data->initial_data->shop_name;
        } catch (Exception $e) {
            return '';
        }
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
     * Get store logo.
     *
     * @return string
     */
    public function getHeaderLogoUrl()
    {
        return Mage::getDesign()->getSkinUrl(Mage::getStoreConfig('design/header/logo_src'));
    }
}
