<?php

/**
 * Authorize button renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Authorize extends
    Mage_Adminhtml_Block_Abstract
{
    /**
     * Set "name" for <button> element
     *
     * @param string $value
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Authorize
     */
    public function setInputName($value)
    {
        $this->setName($value);
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $integration = Mage::registry('current_bold_checkout_integration');
        if (!$integration) {
            return '';
        }
        $buttonText = $integration->getStatus()
            ? Mage::helper('adminhtml')->__('Re-authorize')
            : Mage::helper('adminhtml')->__('Authorize');
        $url = $this->getUrl(
            'adminhtml/integration/authorize',
            ['integration_id' => $integration->getIntegrationId()]
        );
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            [
                'label' => $buttonText,
                'onclick' => 'setLocation(\'' . $url . '\')',
                'class' => 'authorize',
            ]
        );
        return $button->toHtml();
    }
}
