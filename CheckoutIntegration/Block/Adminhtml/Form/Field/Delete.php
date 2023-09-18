<?php

/**
 * Delete integration button renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Delete extends
    Mage_Adminhtml_Block_Abstract
{
    /**
     * Set "name" for <button> element
     *
     * @param string $value
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Delete
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
        $url = $this->getUrl(
            'adminhtml/integration/delete',
            ['integration_id' => $row->getIntegrationId()]
        );

        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(
                [
                    'label' => Mage::helper('adminhtml')->__('Delete Integration'),
                    'onclick' => 'deleteConfirm(\''
                        . Mage::helper('core')->jsQuoteEscape(
                            Mage::helper('adminhtml')->__('Are you sure you want to delete integration?'),
                            true
                        ) . '\', \'' . $url . '\')',
                    'class' => 'delete',
                ]
            );

        return $button->toHtml();
    }
}
