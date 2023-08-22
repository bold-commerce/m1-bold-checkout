<?php

/**
 * Bold order tax exempt block.
 */
class Bold_CheckoutTaxExempt_Block_Info extends Mage_Core_Block_Template
{
    /**
     * @var Mage_Sales_Model_Order|null
     */
    private $order;

    /**
     * @var Bold_Checkout_Model_Order|null
     */
    private $extOrderData;

    /**
     * Get order from registry.
     *
     * @return Mage_Sales_Model_Order|null
     */
    public function getOrder()
    {
        if (!$this->order) {
            $this->order = Mage::registry('current_order');
        }
        return $this->order;
    }

    /**
     * Verify if order has tax exempt.
     *
     * @return bool
     */
    public function isTaxExemptAllowedForOrder()
    {
        return !!$this->getExtOrderData()->getIsTaxExempt();
    }

    /**
     * Get public order id.
     *
     * @return string
     */
    public function getPublicOrderId() {
        return $this->getExtOrderData()->getPublicId();
    }

    /**
     * Retrieve order tax exempt file and comment.
     *
     * @return Mage_Core_Model_Abstract|null
     */
    public function getTaxExemptInfo()
    {
        $extOrderData = $this->getExtOrderData();
        $publicId = $extOrderData->getPublicId();
        $taxExemptFile = $extOrderData->getTaxExemptFile();
        $url = $taxExemptFile
            ? $this->getUrl('checkouttaxexempt/index/download', ['public_id' => $publicId])
            : null;
        return Mage::getModel(
            Bold_CheckoutTaxExempt_Model_TaxExempt_Info::RESOURCE,
            [
                'file_url' => $url,
                'file' => $this->getFileName($taxExemptFile),
                'comment' => $extOrderData->getTaxExemptComment(),
            ]
        );
    }

    /**
     * Get tax-exempt upload document url.
     *
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->getUrl('checkouttaxexempt/index/upload');
    }

    /**
     * Load extension order data.
     *
     * @return Bold_Checkout_Model_Order
     */
    private function getExtOrderData()
    {
        $order = $this->getOrder();
        if ($this->extOrderData) {
            return $this->extOrderData;
        }
        $this->extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $this->extOrderData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);

        return $this->extOrderData;
    }

    /**
     * Get file name from tax exempt document path.
     *
     * @param string $taxExemptFile
     * @return string
     */
    private function getFileName($taxExemptFile)
    {
        $index = strrpos($taxExemptFile, '/');
        if (false !== $index) {
            return substr($taxExemptFile, $index + 1);
        }
        return $taxExemptFile;
    }

    /**
     * Get allowed file extensions.
     *
     * @return string
     */
    public function getAllowedExtensions()
    {
        /** @var Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config $config */
        $config = Mage::getModel(Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE);
        return implode(', ', $config->getAllowedExtensions());
    }
}
