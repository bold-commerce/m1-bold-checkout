<?php

/**
 * HTML select element block with input required options.
 */
class Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_LIfe_Elements_InputRequired
    extends \Mage_Core_Block_Html_Select
{
    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return Bold_Checkout_Block_Adminhtml_System_Config_Form_Field_LIfe_Elements_InputRequired
     */
    public function setInputName($value)
    {
        return $this->setName($value);
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
     * Add style to the element.
     *
     * @return string
     */
    public function getExtraParams()
    {
        return 'style="width: 100px;"';
    }

    /**
     * Retrieve source options.
     *
     * @return array
     */
    private function getSourceOptions()
    {
        return [
            ['label' => 'No', 'value' => 0],
            ['label' => 'Yes', 'value' => 1],
        ];
    }
}
