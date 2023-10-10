<?php

/**
 * HTML select element block with input type options.
 */
class Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Type
    extends \Mage_Core_Block_Html_Select
{
    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_Life_Elements_Type
     */
    public function setInputName($value)
    {
        $this->setName($value);
        return $this;
    }

    /**
     * Add style to the element.
     *
     * @return string
     */
    public function getExtraParams()
    {
        return 'style="width: 100px;"';
    }

    /**
     * @inheirtDoc
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Retrieve source options.
     *
     * @return array
     */
    private function getSourceOptions()
    {
        return [
            ['label' => 'Text', 'value' => 'text'],
            ['label' => 'Textarea', 'value' => 'textarea'],
            ['label' => 'Checkbox', 'value' => 'checkbox'],
            ['label' => 'HTML', 'value' => 'html'],
            ['label' => 'Dropdown', 'value' => 'dropdown'],
            ['label' => 'Datepicker', 'value' => 'datepicker'],
        ];
    }
}
