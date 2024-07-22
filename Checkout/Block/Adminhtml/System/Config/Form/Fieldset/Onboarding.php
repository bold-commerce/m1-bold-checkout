<?php

class Bold_Checkout_Block_Adminhtml_System_Config_Form_Fieldset_Onboarding
    extends Mage_Adminhtml_Block_Template
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'bold/checkout/form/fieldset/onboarding.phtml';
    /**
     * @var Mage_Core_Model_Website|null
     */
    private $_currentWebsite;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->renderView();
    }

    /**
     * @return array{
     *      header: string,
     *      body_text: string,
     *      body_link_text?: string,
     *      body_link_url?: string,
     *      button_text: string,
     *      button_link: string,
     *      sidebar_link_text?: string,
     *      sidebar_link_url?: string
     *  }|null
     */
    public function getOnboardingBannerData()
    {
        $currentWebsite = $this->_getCurrentWebsite();

        if ($currentWebsite === null) {
            return null;
        }

        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $apiUrl = $this->_getBasePlatformConnectorUrl() . '/onboard_banner_data/'
            . (!$config->isCheckoutEnabled($currentWebsite->getId()) ? 'in_progress' : 'complete');
        /** @var array{
         *     header: string,
         *     body_text: string,
         *     body_link_text?: string,
         *     body_link_url?: string,
         *     button_text: string,
         *     button_link: string,
         *     sidebar_link_text?: string,
         *     sidebar_link_url?: string,
         *     meessage?: string,
         *     errors?: array{
         *         message: string,
         *         code: int
         *     }
         * } $result
         */
        $result = json_decode(Bold_Checkout_HttpClient::call('GET', $apiUrl, $currentWebsite->getId()), true);

        if (
            json_last_error() !== JSON_ERROR_NONE
            || array_key_exists('errors', $result)
            || array_key_exists('message', $result)
        ) {
            return null;
        }

        return $result;
    }

    /**
     * @return Mage_Core_Model_Website|null
     */
    private function _getCurrentWebsite()
    {
        if ($this->_currentWebsite !== null) {
            return $this->_currentWebsite;
        }

        $websiteCode = Mage::app()->getRequest()->getParam('website');

        try {
            $this->_currentWebsite = Mage::app()->getWebsite($websiteCode);
        } catch (Mage_Core_Exception $e) {
            return null;
        }

        return $this->_currentWebsite;
    }

    /**
     * @return string
     */
    private function _getBasePlatformConnectorUrl()
    {
        $currentWebsite = $this->_getCurrentWebsite();

        if ($currentWebsite === null) {
            return '';
        }

        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $platformConnectorUrl = $config->getPlatformConnectorUrl($currentWebsite->getId());
        $platformConnectorUrlParts = parse_url($platformConnectorUrl);

        return $platformConnectorUrlParts['scheme'] . '://' . $platformConnectorUrlParts['host'];
    }
}
