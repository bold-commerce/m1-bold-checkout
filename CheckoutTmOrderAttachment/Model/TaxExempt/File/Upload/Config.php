<?php

/**
 * Tax-exempt file upload config rewrite to match order attachments upload config.
 */
class Bold_CheckoutTmOrderAttachment_Model_TaxExempt_File_Upload_Config
    extends Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config
{
    /**
     * @inheritdoc
     */
    public function getUploadPath()
    {
        return Mage::helper('orderattachment')->getBaseDir();
    }

    /**
     * @inheritdoc
     */
    public function getAllowedExtensions()
    {
        /** @var TM_OrderAttachment_Helper_Data $helper */
        $helper = Mage::helper('orderattachment');
        return $helper->getAllowedExtensions();
    }

    /**
     * @inheritdoc
     */
    public function getMaxFileSize()
    {
        /** @var TM_OrderAttachment_Helper_Data $helper */
        $helper = Mage::helper('orderattachment');
        return round($helper->getAllowedFilesize() / 1024);
    }
}
