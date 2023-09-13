<?php

/**
 * Integrations grid renderer.
 */
class Bold_CheckoutIntegration_Block_Adminhtml_Form_Integrations extends
    Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Authorize
     */
    private $authorizeButtonRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Name
     */
    private $integrationNameRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Status
     */
    private $statusRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Delete
     */
    private $deleteButtonRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Key
     */
    private $integrationConsumerKeyRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Secret
     */
    private $integrationConsumerSecretRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Token_Access
     */
    private $integrationAccessTokenRenderer;

    /**
     * @var null|Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Token_Secret
     */
    private $integrationTokenSecretRenderer;

    /**
     * @inheirtDoc
     */
    protected function _construct()
    {
        $this->setData('template', 'bold/checkout_integration/form/integrations.phtml');
        parent::_construct();
    }

    /**
     * @inheirtDoc
     */
    protected function _prepareToRender()
    {
        $integrations = $this->getIntegrations();
        $element = $this->getElement();
        $element->setValue([]);
        $integrationsData = [];
        foreach ($integrations as $integration) {
            $integrationsData[] = $integration->getData();
        }
        $element->setValue($integrationsData);
        $this->addColumn(
            'integration_name',
            [
                'label' => Mage::helper('adminhtml')->__('Integration Name'),
                'style' => 'width:200px',
                'renderer' => $this->getIntegrationNameRenderer(),
            ]
        );
        $this->addColumn(
            'integration_status',
            [
                'label' => Mage::helper('adminhtml')->__('Integration Status'),
                'style' => 'width:120px',
                'renderer' => $this->getStatusRenderer(),
            ]
        );
        $this->addColumn(
            'consumer_key',
            [
                'label' => Mage::helper('adminhtml')->__('Consumer Key'),
                'style' => 'width:200px',
                'renderer' => $this->getIntegrationConsumerKeyRenderer(),
            ]
        );
        $this->addColumn(
            'consumer_secret',
            [
                'label' => Mage::helper('adminhtml')->__('Consumer Secret'),
                'style' => 'width:200px',
                'renderer' => $this->getIntegrationConsumerSecretRenderer(),
            ]
        );
        $this->addColumn(
            'access_token',
            [
                'label' => Mage::helper('adminhtml')->__('Access Token'),
                'style' => 'width:200px',
                'renderer' => $this->getIntegrationAccessTokenRenderer(),
            ]
        );
        $this->addColumn(
            'token_secret',
            [
                'label' => Mage::helper('adminhtml')->__('Access Token Secret'),
                'style' => 'width:200px',
                'renderer' => $this->getIntegrationTokenSecretRenderer(),
            ]
        );
        $this->addColumn(
            'action',
            [
                'label' => Mage::helper('adminhtml')->__('Action'),
                'renderer' => $this->getAuthorizeButtonRenderer(),
            ]
        );
        $this->addColumn(
            'delete',
            [
                'label' => Mage::helper('adminhtml')->__(' '),
                'renderer' => $this->getDeleteButtonRenderer(),
            ]
        );
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName, $row)
    {
        $column = $this->_columns[$columnName];
        if ($column['renderer']) {
            return $column['renderer']
                ->setColumnName($columnName)
                ->setColumn($column)
                ->setRow($row)
                ->toHtml();
        }
        return '';
    }

    /**
     * Load integration by website id.
     *
     * @return Bold_CheckoutIntegration_Model_Integration[]
     * @throws Mage_Core_Exception
     */
    private function getIntegrations()
    {
        $element = $this->getElement();
        $websiteId = (int)$element->getScopeId();
        return Bold_CheckoutIntegration_Model_IntegrationService::findByWebsiteId($websiteId);
    }

    /**
     * Retrieve renderer for integration authorize button.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Authorize
     */
    private function getAuthorizeButtonRenderer()
    {
        if (!$this->authorizeButtonRenderer) {
            $this->authorizeButtonRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_authorize',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->authorizeButtonRenderer;
    }

    /**
     * Retrieve renderer for integration status.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Status
     */
    private function getStatusRenderer()
    {
        if (!$this->statusRenderer) {
            $this->statusRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_status',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->statusRenderer;
    }

    /**
     * Retrieve renderer for integration name.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Name
     */
    private function getIntegrationNameRenderer()
    {
        if (!$this->integrationNameRenderer) {
            $this->integrationNameRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_name',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->integrationNameRenderer;
    }

    /**
     * Retrieve renderer for delete button.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Delete
     */
    private function getDeleteButtonRenderer()
    {
        if (!$this->deleteButtonRenderer) {
            $this->deleteButtonRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_delete',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->deleteButtonRenderer;
    }

    /**
     * Retrieve renderer for integration consumer key.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Key
     */
    private function getIntegrationConsumerKeyRenderer()
    {
        if (!$this->integrationConsumerKeyRenderer) {
            $this->integrationConsumerKeyRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_consumer_key',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->integrationConsumerKeyRenderer;
    }

    /**
     * Retrieve renderer for integration consumer key.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Consumer_Secret
     */
    private function getIntegrationConsumerSecretRenderer()
    {
        if (!$this->integrationConsumerSecretRenderer) {
            $this->integrationConsumerSecretRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_consumer_secret',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->integrationConsumerSecretRenderer;
    }

    /**
     * Retrieve renderer for integration token secret.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Token_Access
     */
    private function getIntegrationAccessTokenRenderer()
    {
        if (!$this->integrationAccessTokenRenderer) {
            $this->integrationAccessTokenRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_token_access',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->integrationAccessTokenRenderer;
    }

    /**
     * Retrieve renderer for integration token secret.
     *
     * @return Bold_CheckoutIntegration_Block_Adminhtml_Form_Field_Token_Secret
     */
    private function getIntegrationTokenSecretRenderer()
    {
        if (!$this->integrationTokenSecretRenderer) {
            $this->integrationTokenSecretRenderer = $this->getLayout()->createBlock(
                'bold_checkout_integration/adminhtml_form_field_token_secret',
                '',
                ['is_render_to_js_template' => true]
            );
        }
        return $this->integrationTokenSecretRenderer;
    }
}
