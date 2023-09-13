<?php

/**
 * Integration status column renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Status extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $integration = Mage::registry('current_bold_checkout_integration');
        if (!$integration) {
            return '<span class="grid-severity-notice"><span>'
                . Mage::helper('adminhtml')->__('Not created')
                . '</span></span>';
        }
        $active = Mage::helper('adminhtml')->__('Active');
        $inactive = Mage::helper('adminhtml')->__('Inactive');
        return $integration->getStatus()
            ? '<span class="grid-severity-notice"><span>' . $active . '</span></span>'
            : '<span class="grid-severity-critical"><span>' . $inactive . '</span></span>';
    }
}
