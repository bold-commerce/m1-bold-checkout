<?php

/**
 * Integration consumer key column renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Key extends
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
        $consumerKey = $row ? $row->getConsumerKey() : 'None';
        return '<span class="grid-row-title"><span>' . $consumerKey . '</span></span>';
    }
}
