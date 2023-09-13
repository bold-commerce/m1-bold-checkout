<?php

/**
 * Integration name column renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Name extends
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
        $integrationName = $row ? $row->getName() : 'None';
        return '<span class="grid-row-title"><span>' . $integrationName . '</span></span>';
    }
}
