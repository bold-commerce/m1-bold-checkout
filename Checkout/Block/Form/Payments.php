<?php

/**
 * Bold payments block for checkout.
 */
class Bold_Checkout_Block_Form_Payments extends Mage_Payment_Block_Form
{
    const PATH = '/checkout/storefront/';

    /**
     * @var array
     */
    private $countries;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        if ($this->getAction() instanceof TM_FireCheckout_IndexController) {
            $this->setTemplate('bold/checkout_tm_fire_checkout/onestep/form/payments.phtml');
            return;
        }
        $this->setTemplate('bold/checkout/form/payments.phtml');
    }

    /**
     * Get customer saved addresses.
     *
     * @return string
     */
    public function getSavedAddresses()
    {
        $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return json_encode([]);
        }
        $addresses = isset($boldCheckoutData->data->application_state->customer->saved_addresses)
            ? $boldCheckoutData->data->application_state->customer->saved_addresses
            : [];
        return json_encode($addresses);
    }

    /**
     * Get is quote customer is guest.
     *
     * @return int
     */
    public function customerIsGuest()
    {
        return (int)!Mage::getSingleton('checkout/session')->getQuote()->getCustomer()->getId();
    }

    /**
     * Get allowed countries.
     *
     * @return string
     * @throws Mage_Core_Exception
     */
    public function getAllowedCountries()
    {
        if ($this->countries) {
            return json_encode($this->countries);
        }
        $storeId = Mage::getSingleton('checkout/session')->getQuote()->getStoreId();
        /** @var Mage_Directory_Model_Resource_Country_Collection $countriesCollection */
        $countriesCollection = Mage::getModel('directory/country')->getCollection();
        $countriesCollection->loadByStore($storeId);
        foreach ($countriesCollection as $country) {
            $enLocale = Mage::getModel('core/locale', Mage_Core_Model_Locale::DEFAULT_LOCALE);
            $this->countries[] = [
                'value' => $country->getCountryId(),
                'label' => $enLocale->getTranslation($country->getCountryId(), 'country'),
            ];
        }
        return json_encode($this->countries);
    }

    /**
     * Get storefront Bold client url.
     *
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getStoreFrontClientUrl()
    {
        $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return null;
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $shopId = $config->getShopIdentifier((int)Mage::app()->getWebsite()->getId());
        return Bold_Checkout_StorefrontClient::URL . $shopId . '/' . $boldCheckoutData->data->public_order_id . '/';
    }

    /**
     * Get storefront Bold client jwt token.
     *
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getJwtToken()
    {
        $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return null;
        }
        return $boldCheckoutData->data->jwt_token;
    }

    /**
     * Get payment iframe url.
     *
     * @return string|null
     * @throws Mage_Core_Exception
     */
    public function getIframeUrl()
    {
        $boldCheckoutData = Mage::getSingleton('checkout/session')->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return null;
        }
        $websiteId = Mage::app()->getWebsite()->getId();
        try {
            $this->refreshOrder();
        } catch (Mage_Core_Exception $e) {
            return null;
        }
        $shopId = Bold_Checkout_Service_ShopIdentifier::getShopIdentifier($websiteId);
        $styles = $this->getStyles();
        if ($styles) {
            Bold_Checkout_StorefrontClient::call('POST', 'payments/styles', $styles);
        }
        $orderId = $boldCheckoutData->data->public_order_id;
        $jwtToken = $boldCheckoutData->data->jwt_token;
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getModel(Bold_Checkout_Model_Config::RESOURCE);
        $apiUrl = $config->getApiUrl($websiteId);
        return $apiUrl . self::PATH . $shopId . '/' . $orderId . '/payments/iframe?token=' . $jwtToken;
    }

    /**
     * Get payment iframe styles from file.
     *
     * @return array|null
     */
    private function getStyles()
    {
        $styles = Mage::getModuleDir('data', 'Bold_Checkout') . DS . 'form/payments/styles.json';
        if (file_exists($styles)) {
            return json_decode(file_get_contents($styles), true);
        }
        return null;
    }

    /**
     * Refresh order on Bold side before render PIGI iframe.
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    private function refreshOrder()
    {
        $session = Mage::getSingleton('checkout/session');
        $boldCheckoutData = $session->getBoldCheckoutData();
        if (!$boldCheckoutData) {
            return;
        }
        if ($this->customerIsGuest() && !$boldCheckoutData->data->application_state->customer->email_address) {
            $guestResult = Bold_Checkout_StorefrontClient::call(
                'POST',
                'customer/guest',
                [
                    'email_address' => $session->getQuote()->getBillingAddress()->getEmail(),
                    'first_name' => $session->getQuote()->getBillingAddress()->getFirstname(),
                    'last_name' => $session->getQuote()->getBillingAddress()->getLastname(),
                ]
            );
            if (isset($guestResult->errors)) {
                Mage::throwException($guestResult->errors[0]->message);
            }
        }

        if (!$boldCheckoutData->data->application_state->addresses->billing) {
            $refreshResult = Bold_Checkout_StorefrontClient::call(
                'GET',
                'refresh'
            );
            if (isset($refreshResult->errors)) {
                Mage::throwException($refreshResult->errors[0]->message);
            }
        }
    }
}
