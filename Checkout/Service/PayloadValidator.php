<?php

/**
 * Required fields payload validator.
 */
class Bold_Checkout_Service_PayloadValidator
{
    /**
     * Validate payload against required fields.
     *
     * @param stdClass $payload
     * @param array $requiredFields
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function validate(stdClass $payload, array $requiredFields)
    {
        $errors = [];
        foreach ($requiredFields as $field) {
            if (!isset($payload->$field)) {
                $errors[] = Mage::helper('core')->__('Filed "%s" is required.', $field);
            }
        }
        if ($errors) {
            Mage::throwException(implode(PHP_EOL, $errors));
        }
    }
}
