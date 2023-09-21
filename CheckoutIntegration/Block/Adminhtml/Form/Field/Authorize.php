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
        $row = $this->getRow();
        $buttonText = $row->getStatus()
            ? Mage::helper('adminhtml')->__('Re-authorize')
            : Mage::helper('adminhtml')->__('Authorize');
        $url = $this->getUrl(
            'adminhtml/integration/authorize',
            ['integration_id' => $row->getIntegrationId()]
        );
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            [
                'label' => $buttonText,
                'onclick' => $row->getIdentityLinkUrl() ? 'setLocation(\'' . $url . '\')' : '',
                'class' => $row->getIdentityLinkUrl() ? 'authorize' : 'disabled',
            ]
        );
        return $button->toHtml();
    }
}
