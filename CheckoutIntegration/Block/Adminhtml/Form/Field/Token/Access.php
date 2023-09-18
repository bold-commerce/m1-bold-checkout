<?php

/**
 * Integration consumer key column renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Token_Access extends
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
        $token = 'None';
        if ($row) {
            $integration = Bold_CheckoutIntegration_Model_IntegrationService::get($row->getIntegrationId());
            $token = $integration->getToken();
        }
        return '<span class="grid-row-title"><span>' . $token . '</span></span>';
    }
}
