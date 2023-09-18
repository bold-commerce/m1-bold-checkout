<?php

/**
 * Integration consumer secret column renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Secret extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Add style to the element.
     *
     * @return string
     */
    public function getExtraParams()
    {
        return 'style="width:200px;"';
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        $row = $this->getRow();
        $consumerSecret = $row ? $row->getSecret() : 'None';
        return '<span class="grid-row-title"><span>' . $consumerSecret . '</span></span>';
    }
}
