<?php

/**
 * "Bold Checkout Template File" system configuration backend class.
 */
class Bold_CheckoutSelfHosted_Model_System_Config_Backend_File extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    /**
     * @inheritDoc
     */
    protected function _getAllowedExtensions()
    {
        return ['js'];
    }
}
