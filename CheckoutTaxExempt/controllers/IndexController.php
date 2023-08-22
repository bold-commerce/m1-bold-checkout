<?php

/**
 * Bold checkout tax exempt main controller.
 */
class Bold_CheckoutTaxExempt_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Render upload guest tax-exempt document form page.
     *
     * @return void
     */
    public function viewAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->_redirect('/');
            return;
        }
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
        $order = Mage::getModel(Mage_Sales_Model_Order::class)->load($extOrderData->getOrderId());
        if ($order->getCustomerId()) {
            $this->_redirect('/');
            return;
        }
        if (!$extOrderData->getOrderId()) {
            $this->_redirect('/');
            return;
        }
        Mage::register('current_order', $order);
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Upload tax-exempt document and comment.
     *
     * @return void
     * @throws Exception
     */
    public function uploadAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirect('/');
            return;
        }
        $publicId = $this->getRequest()->getPost('public_id');
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($publicId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
        $orderId = $extOrderData->getOrderId();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$this->isAllowed($extOrderData, $order)) {
            Mage::getSingleton('core/session')->addError(
                $this->__('Cannot upload tax exempt document for given order.')
            );
            $this->_redirectReferer();
            return;
        }
        try {
            $extOrderData->setTaxExemptComment($this->getRequest()->getPost('tax_exempt_comment'));
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
        $publicId = $this->getRequest()->getParam('public_id');
        if (!$publicId) {
            $this->_redirectReferer();
        }
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($publicId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
        $orderId = $extOrderData->getOrderId();
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);
        if (!$this->isAllowed($extOrderData, $order)) {
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
     * Check if customer is allowed to upload tax exempt document.
     *
     * @param Bold_Checkout_Model_Order $extOrderData
     * @param Mage_Sales_Model_Order $order
     * @param string $publicId
     * @return bool
     */
    private function isAllowed(Bold_Checkout_Model_Order $extOrderData, Mage_Sales_Model_Order $order)
    {
        if (!$extOrderData->getIsTaxExempt()) {
            return false;
        }
        if (!$order->getCustomerId()) {
            return true;
        }
        return (int)$order->getCustomerId() === (int)Mage::getSingleton('customer/session')->getCustomerId();
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
