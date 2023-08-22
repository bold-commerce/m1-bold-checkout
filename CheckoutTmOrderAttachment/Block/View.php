<?php

/**
 * Hide attachment block if order is tax exempt.
 */
class Bold_CheckoutTmOrderAttachment_Block_View extends TM_OrderAttachment_Block_View
{
    /**
     * @var Bold_Checkout_Model_Order
     */
    private $extOrderData;

    /**
     * @inheritDoc
     */
    public function getTemplate()
    {
        if ($this->getExtOrderData()->getPublicId()) {
            return false;
        }
        return parent::getTemplate();
    }

    /**
     * Load extension order data.
     *
     * @return Bold_Checkout_Model_Order
     */
    private function getExtOrderData()
    {
        if ($this->extOrderData) {
            return $this->extOrderData;
        }
        $this->extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $this->extOrderData->load($this->getOrderId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);

        return $this->extOrderData;
    }
}
