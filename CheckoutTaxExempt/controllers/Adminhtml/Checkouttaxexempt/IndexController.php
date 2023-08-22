<?php

/**
 * Manage tax-exempt document controller.
 */
class Bold_CheckoutTaxExempt_Adminhtml_CheckoutTaxExempt_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Upload tax-exempt document action.
     *
     * @return void
     * @throws Exception
     */
    public function uploadAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }
        $orderId = $this->getRequest()->getPost('order_id');
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderId, Bold_Checkout_Model_Resource_Order::ORDER_ID);
        if (!$extOrderData->getIsTaxExempt()) {
            Mage::getSingleton('core/session')->addError(
                $this->__('Cannot upload tax exempt document for given order.')
            );
            $this->_redirectReferer();
            return;
        }
        $extOrderData->setTaxExemptComment($this->getRequest()->getPost('tax_exempt_comment'));
        try {
            /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload $uploader */
            $uploader = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload::RESOURCE);
            $uploader->upload($extOrderData);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError(
                $this->__('Cannot upload tax exempt document. %s', $e->getMessage())
            );
            $this->_redirectReferer();
            return;
        }
        Mage::getSingleton('core/session')->addSuccess(
            $this->__('Your tax exempt document has been successfully uploaded.')
        );
        $this->_redirectReferer();
    }

    /**
     * Download tax-exempt document.
     *
     * @return void
     */
    public function downloadAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->_redirectReferer();
        }
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderId, Bold_Checkout_Model_Resource_Order::ORDER_ID);
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$extOrderData->getIsTaxExempt()) {
            Mage::getSingleton('core/session')->addError(
                $this->__('Cannot download tax exempt document for given order.')
            );
            $this->_redirectReferer();
            return;
        }
        /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config $config */
        $config = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE);
        $uploadPath = $config->getUploadPath();
        $filePath = $uploadPath . $extOrderData->getTaxExemptFile();
        //@phpcs:disable MEQP1.Security.DiscouragedFunction.Found
        if (!file_exists($filePath)) {
            Mage::getSingleton('core/session')->addError(
                $this->__('File was not found.')
            );
            $this->_redirectReferer();
            return;
        }
        $this->_prepareDownloadResponse(
            $this->getFormattedFileName($extOrderData->getTaxExemptFile()),
            [
                'type' => 'filename',
                'value' => $filePath,
            ]
        );
        $this->_redirectReferer();
    }

    /**
     * Remove tax-exempt from order.
     *
     * @return void
     * @throws Exception
     */
    public function deleteAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }
        $orderId = $this->getRequest()->getParam('order_id');
        Bold_CheckoutTaxExempt_Model_TaxExempt_File_Delete::delete($orderId);
        Mage::getSingleton('core/session')->addSuccess(
            $this->__('Your tax exempt document has been successfully deleted.')
        );
        $this->_redirectReferer();
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }

    /**
     * Get file name from tax exempt document path.
     *
     * @param string $taxExemptFile
     * @return string
     */
    private function getFormattedFileName($taxExemptFile)
    {
        $index = strrpos($taxExemptFile, '/');
        if (false !== $index) {
            return substr($taxExemptFile, $index + 1);
        }
        return $taxExemptFile;
    }
}
