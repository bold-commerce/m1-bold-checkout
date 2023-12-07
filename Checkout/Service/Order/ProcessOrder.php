<?php

/**
 * Update Magento order payment information.
 */
class Bold_Checkout_Service_Order_ProcessOrder
{
    /**
     * Update order payment.
     *
     * Due to order is placed on Magento side in case of self-hosted checkout with Magento storefront
     * only payment information should be updated.
     *
     * @param stdClass $payload
     * @return Mage_Sales_Model_Order
     * @throws Mage_Core_Exception
     */
    public static function process(stdClass $payload)
    {
        $attempt = 1;
        do {
            /** @var Bold_Checkout_Model_Order $extOrderData */
            $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
            $extOrderData->load($payload->order->publicId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
            $orderId = $extOrderData->getOrderId();
            if (!$orderId) {
                $attempt++;
                sleep(1);
            }
        } while (!$orderId && $attempt < 5);
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($extOrderData->getOrderId());
        if (!$order->getId()) {
            Mage::throwException(Mage::helper('core')->__('Order not found'));
        }
        Bold_Checkout_Service_Order_Payment::processPayment(
            $order,
            $payload->order->payment,
            $payload->order->transaction
        );
        Mage::dispatchEvent('bold_order_process_after', ['order' => $order, 'payload' => $payload]);
        return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
    }
}
