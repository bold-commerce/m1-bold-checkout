<?php

/**
 * Order entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order
{
    /**
     * Extract orders data.
     *
     * @param Mage_Sales_Model_Order[] $orders
     * @param bool $includePaymentInformation
     * @return array
     */
    public static function extract(array $orders, $includePaymentInformation = true)
    {
        $result = [];
        foreach ($orders as $order) {
            $result[] = self::extractOrder($order, $includePaymentInformation);
        }

        return $result;
    }

    /**
     * Extract order entity data into array.
     *
     * @param Mage_Sales_Model_Order $order
     * @param bool $includePaymentInformation
     * @return array
     */
    public static function extractOrder(Mage_Sales_Model_Order $order, $includePaymentInformation = true)
    {
        $orderExtData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $orderExtData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        $result = [
            'platform_id' => $order->getIncrementId(),
            'platform_updated_at' => (string)Mage::getSingleton('core/date')->date(
                'Y-m-d\TH:i:s\Z',
                strtotime($order->getUpdatedAt())
            ),
            'order_number' => (string)$order->getExtOrderId(),
            'platform_customer_id' => (string)$order->getCustomerId(),
            'shipping_method' => (string)$order->getShippingMethod(),
            'browser_ip' => (string)$order->getRemoteIp(),
            'source' => '',
            'created_via' => 'checkout',
            'locale' => Mage::app()->getLocale()->getLocaleCode(),
            'test' => false,
            'notes' => self::getNotes(array_values($order->getAllStatusHistory())),
            'public_notes' => self::getNotes(array_values($order->getVisibleStatusHistory())),
            'shipping_subtotal' => (string)$order->getBaseShippingAmount(),
            'shipping_tax' => (string)$order->getBaseShippingTaxAmount(),
            'shipping_taxes' => [],
            'discount' => (string)$order->getBaseDiscountAmount(),
            'subtotal' => (string)$order->getBaseSubtotal(),
            'subtotal_tax' => (string)$order->getBaseTaxAmount(),
            'total_tax' => (string)$order->getBaseTaxAmount(),
            'total' => (string)$order->getBaseGrandTotal(),
            'refunded_amount' => (string)$order->getBaseTotalOnlineRefunded(),
            'currency' => $order->getBaseCurrencyCode(),
            'order_status' => $order->isCanceled() ? 'cancelled' : 'active',
            'fulfillment_status' => $orderExtData->getFulfillmentStatus(),
            'financial_status' => $orderExtData->getFinancialStatus(),
            'placed_at' => Mage::getSingleton('core/date')->date(
                'Y-m-d\TH:i:s\Z',
                strtotime($order->getCreatedAt())
            ),
            'line_items' => Bold_Checkout_Service_Extractor_Order_Item::extract($order),
        ];
        if ($includePaymentInformation) {
            $result['payments'] = self::extractPayments($order->getAllPayments());
        }
        if ($order->getShippingAddress()) {
            $result['shipping_addresses'] = [
                Bold_Checkout_Service_Extractor_Order_Address::extract($order->getShippingAddress()),
            ];
        }
        if ($order->getBillingAddress()) {
            $result['billing_addresses'] = Bold_Checkout_Service_Extractor_Order_Address::extract(
                $order->getBillingAddress()
            );
        }
        return $result;
    }

    /**
     * Get order notes.
     *
     * @param array $orderStatusHistory
     * @return string
     */
    private static function getNotes(array $orderStatusHistory)
    {
        $notes = '';
        /** @var Mage_Sales_Model_Order_Status_History $status */
        foreach ($orderStatusHistory as $status) {
            $notes .= $status->getComment() . PHP_EOL;
        }
        return $notes;
    }

    /**
     * @param array $orderPayments
     * @return Mage_Sales_Model_Entity_Order_Payment[]
     */
    private static function extractPayments(array $orderPayments)
    {
        $result = [];
        foreach ($orderPayments as $payment) {
            $result[] = Bold_Checkout_Service_Extractor_Order_Payment::extract($payment);
        }

        return $result;
    }
}
