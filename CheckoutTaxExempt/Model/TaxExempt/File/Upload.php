<?php

/**
 * Upload tax-exempt document service.
 */
class Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload
{
    const RESOURCE = 'bold_checkouttaxexempt/taxexempt_file_upload';
    
    /**
     * Upload tax-exempt document.
     *
     * @param Bold_Checkout_Model_Order $extOrderData
     * @return void
     * @throws Exception
     */
    public function upload(Bold_Checkout_Model_Order $extOrderData)
    {
        /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config $uploadConfig */
        $uploadConfig = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE);
        $uploader = new Varien_File_Uploader('tax_exempt_file');
        $uploader->setAllowedExtensions($uploadConfig->getAllowedExtensions());
        $uploader->addValidateCallback('filesize', $this, 'validateFilesize');
        $uploader->setAllowCreateFolders(true);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $uploader->setFilenamesCaseSensitivity(false);
        $path = $uploadConfig->getUploadPath();
        $uploader->save($path);
        if (!$uploader->getUploadedFileName()) {
            Mage::throwException('File was not uploaded.');
        };
        @unlink(
            $path . $extOrderData->getTaxExemptFile()
        );
        $extOrderData->setTaxExemptFile($uploader->getUploadedFileName());
        $extOrderData->save();
        Mage::dispatchEvent(
            'bold_checkout_tax_exempt_document_upload_after',
            [
                'order_id' => $extOrderData->getOrderId(),
                'file' => $uploader->getUploadedFileName(),
                'comment' => $extOrderData->getTaxExemptComment(),
            ]
        );
    }

    /**
     * Validate file size callback.
     *
     * @param string $filePath
     * @return bool
     * @throws Mage_Core_Exception
     */
    public function validateFilesize($filePath)
    {
        /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config $uploadConfig */
        $uploadConfig = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE);
        $maxSize = $uploadConfig->getMaxFileSize();
        $filesize = filesize($filePath);
        if (round($filesize / (1024 * 1024)) > $maxSize) {
            Mage::throwException(
                Mage::helper('core')->__('Files may not exceed %sMB', $maxSize)
            );
        }
        return true;
    }
}
