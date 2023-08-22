<?php

/**
 * Bold Checkout order tax-exempt attachments tab.
 */
class Bold_CheckoutTaxExempt_Block_Adminhtml_Sales_Order_View_Tab_Attachments
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @var Bold_Checkout_Model_Order
     */
    private $extOrderData;

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('bold/checkouttaxexempt/sales/order/view/tab/attachments.phtml');
    }

    /**
     * @inheritDoc
     */
    public function getTabLabel()
    {
        return $this->__('Order Attachments');
    }

    /**
     * @inheritDoc
     */
    public function getTabTitle()
    {
        return $this->__('Order Attachments');
    }

    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        return $this->getOrderExtData()->getIsTaxExempt();
    }

    /**
     * Get order extension data.
     *
     * @return Bold_Checkout_Model_Order
     */
    private function getOrderExtData()
    {
        $order = $this->getOrder();
        if (!$this->extOrderData) {
            $this->extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
            $this->extOrderData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        }

        return $this->extOrderData;
    }

    /**
     * Retrieve current order.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        return !$this->getOrderExtData()->getIsTaxExempt();
    }

    /**
     * Get tax-exempt document url.
     *
     * @return string|null
     */
    public function getTaxExemptFileUrl()
    {
        $orderId = $this->getOrder()->getEntityId();
        $taxExemptFile = $this->getOrderExtData()->getTaxExemptFile();
        return $taxExemptFile
            ? $this->getUrl('adminhtml/checkouttaxexempt_index/download', ['order_id' => $orderId])
            : null;
    }

    /**
     * Get tax-exempt upload document url.
     *
     * @return string
     */
    public function getUploadUrl()
    {
        return $this->getUrl('adminhtml/checkouttaxexempt_index/upload');
    }

    /**
     * Get delete tax-exempt url.
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl(
            'adminhtml/checkouttaxexempt_index/delete',
            [
                'order_id' => $this->getOrder()->getId(),
                'form_key' => $this->getFormKey(),
            ]
        );
    }

    /**
     * Get tax-exempt document name.
     *
     * @return string|null
     */
    public function getTaxExemptFileName()
    {
        return $this->getOrderExtData()->getTaxExemptFile();
    }

    /**
     * Get tax-exempt comment.
     *
     * @return string|null
     */
    public function getTaxExemptComment()
    {
        return $this->getOrderExtData()->getTaxExemptComment();
    }

    /**
     * Get allowed file extensions.
     *
     * @return string
     */
    public function getAllowedExtensions()
    {
        $extensions = Mage::getModel(
            Bold_CheckoutTaxExempt_Model_TaxExempt_File_Upload_Config::RESOURCE
        )->getAllowedExtensions();
        return implode(', ', $extensions);
    }

    /**
     * Get public order id.
     *
     * @return string
     */
    public function getPublicOrderId()
    {
        return $this->getOrderExtData()->getPublicId();
    }
}
