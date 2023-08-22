<?php

/**
 * Order additional data model.
 */
class Bold_Checkout_Model_Order extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout/order';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(Bold_Checkout_Model_Order::RESOURCE);
    }

    /**
     * Set order entity id.
     *
     * @param int $orderId
     * @return void
     */
    public function setOrderId($orderId)
    {
        $this->setData(Bold_Checkout_Model_Resource_Order::ORDER_ID, $orderId);
    }

    /**
     * Retrieve order id.
     *
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Order::ORDER_ID)
            ? (int)$this->getData(Bold_Checkout_Model_Resource_Order::ORDER_ID)
            : null;
    }

    /**
     * Set order public id.
     *
     * @param string $publicId
     * @return void
     */
    public function setPublicId($publicId)
    {
        $this->setData(Bold_Checkout_Model_Resource_Order::PUBLIC_ID, $publicId);
    }

    /**
     * Retrieve public order id.
     *
     * @return string|null
     */
    public function getPublicId()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
    }

    /**
     * Set order financial status.
     *
     * @param string $financialStatus
     * @return void
     */
    public function setFinancialStatus($financialStatus)
    {
        $this->setData(Bold_Checkout_Model_Resource_Order::FINANCIAL_STATUS, $financialStatus);
    }

    /**
     * Retrieve financial order status.
     *
     * @return string|null
     */
    public function getFinancialStatus()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Order::FINANCIAL_STATUS);
    }

    /**
     * Set order fulfillment status.
     *
     * @param string $fulfillmentStatus
     * @return void
     */
    public function setFulfillmentStatus($fulfillmentStatus)
    {
        $this->setData(Bold_Checkout_Model_Resource_Order::FULFILLMENT_STATUS, $fulfillmentStatus);
    }

    /**
     * Retrieve fulfillment order status.
     *
     * @return string|null
     */
    public function getFulfillmentStatus()
    {
        return $this->getData(Bold_Checkout_Model_Resource_Order::FULFILLMENT_STATUS);
    }

    /**
     * Set is order using delayed payment capture.
     *
     * @param int $isDelayedCapture
     * @return void
     */
    public function setIsDelayedCapture($isDelayedCapture)
    {
        $this->setData(Bold_Checkout_Model_Resource_Order::IS_DELAYED_CAPTURE, $isDelayedCapture);
    }

    /**
     * Retrieve is order using delayed payment capture flag.
     *
     * @return int
     */
    public function getIsDelayedCapture()
    {
        return (int)$this->getData(Bold_Checkout_Model_Resource_Order::IS_DELAYED_CAPTURE);
    }
}
