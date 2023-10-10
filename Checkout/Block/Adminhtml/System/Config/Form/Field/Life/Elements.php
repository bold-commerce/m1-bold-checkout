<?php

/**
 * "Lightweight Frontend Experience (LiFE) Elements" dynamic row.
 */
class Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Location
     */
    private $locationRenderer;

    /**
     * @var Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Type
     */
    private $inputTypeRenderer;

    /**
     * @var Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Required
     */
    private $inputRequiredRenderer;

    /**
     * @inheirtDoc
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'location',
            [
                'label' => Mage::helper('core')->__('Location'),
                'renderer' => $this->getLocationRenderer(),
            ]
        );
        $this->addColumn(
            'input_type',
            [
                'label' => Mage::helper('core')->__('Type'),
                'renderer' => $this->getInputTypeRenderer(),
            ]
        );
        $this->addColumn(
            'input_required',
            [
                'label' => Mage::helper('core')->__('Required'),
                'renderer' => $this->getInputRequiredRenderer(),
            ]
        );
        $this->addColumn(
            'meta_data_field',
            [
                'label' => Mage::helper('core')->__('Field key'),
                'class' => 'input-text required-entry',
                'style' => 'width:50px',
            ]
        );
        $this->addColumn(
            'input_label',
            [
                'label' => Mage::helper('core')->__('Label'),
                'style' => 'width:100px',
            ]
        );
        $this->addColumn(
            'input_placeholder',
            [
                'label' => Mage::helper('core')->__('Placeholder'),
                'style' => 'width:220px',
            ]
        );
        $this->addColumn(
            'input_default',
            [
                'label' => Mage::helper('core')->__('Default'),
                'style' => 'width:100px',
            ]
        );
        $this->addColumn(
            'input_regex',
            [
                'label' => Mage::helper('core')->__('Validation (RegEx)'),
                'style' => 'width:100px',
            ]
        );
        $this->addColumn(
            'order_asc',
            [
                'label' => Mage::helper('core')->__('Index'),
                'class' => 'input-text validate-greater-than-zero',
                'style' => 'width:50px',
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('core')->__('Add');
    }

    /**
     * @inheirtDoc
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        if ($row->getData('location')) {
            $row->setData(
                'option_extra_attr_' . $this->getLocationRenderer()->calcOptionHash($row->getData('location')),
                'selected="selected"'
            );
        }
        if ($row->getData('input_type')) {
            $row->setData(
                'option_extra_attr_' . $this->getInputTypeRenderer()->calcOptionHash($row->getData('input_type')),
                'selected="selected"'
            );
        }
        if ($row->getData('input_required')) {
            $inputRequired = $this->getInputRequiredRenderer()->calcOptionHash($row->getData('input_required'));
            $row->setData(
                'option_extra_attr_' . $inputRequired,
                'selected="selected"'
            );
        }
    }

    /**
     * Retrieve renderer for location element.
     *
     * @return Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Location
     */
    private function getLocationRenderer()
    {
        if (!$this->locationRenderer) {
            $this->locationRenderer = $this->getLayout()->createBlock(
                'bold_checkout/adminhtml_system_config_form_field_life_elements_location',
                '',
                ['is_render_to_js_template' => true]
            );
        }

        return $this->locationRenderer;
    }

    /**
     * Retrieve renderer for input type element.
     *
     * @return Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Type
     */
    private function getInputTypeRenderer()
    {
        if (!$this->inputTypeRenderer) {
            $this->inputTypeRenderer = $this->getLayout()->createBlock(
                'bold_checkout/adminhtml_system_config_form_field_life_elements_type',
                '',
                ['is_render_to_js_template' => true]
            );
        }

        return $this->inputTypeRenderer;
    }

    /**
     * Retrieve renderer for input required element.
     *
     * @return Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Required
     */
    private function getInputRequiredRenderer()
    {
        if (!$this->inputRequiredRenderer) {
            $this->inputRequiredRenderer = $this->getLayout()->createBlock(
                'bold_checkout/adminhtml_system_config_form_field_life_elements_required',
                '',
                ['is_render_to_js_template' => true]
            );
        }

        return $this->inputRequiredRenderer;
    }
}
