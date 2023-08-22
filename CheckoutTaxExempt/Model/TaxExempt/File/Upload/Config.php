<?php

/**
 * Tax-exempt file upload config.
 */
class Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config
{
    const RESOURCE = 'bold_checkouttaxexempt/taxexempt_file_upload_config';
    const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
    const MAX_FILE_SIZE_MB = 25;

    /**
     * Get tax-exempt file upload path.
     *
     * @return string
     */
    public function getUploadPath()
    {
        return Mage::getBaseDir('var') . DS . 'bold' . DS . 'tax_exempt_documents';
    }

    /**
     * Get allowed tax-exempt file extensions.
     *
     * @return array
     */
    public function getAllowedExtensions()
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * Get max tax-exempt file size in MB.
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return self::MAX_FILE_SIZE_MB;
    }
}
