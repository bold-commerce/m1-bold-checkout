<?php

/**
 * Create bold order from quote.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_CreateOrderFromQuote
{
    /**
     * Create and place bold order from quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param stdClass $orderPayload
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public static function create(Mage_Sales_Model_Quote $quote, stdClass $orderPayload)
    {
        /** @var Mage_Sales_Model_Service_Quote $quoteService */
        $quoteService = Mage::getModel('sales/service_quote', $quote);
        $orderData = ['ext_order_id' => $orderPayload->order_number, 'remote_ip' => $orderPayload->browser_ip];
        $customAttributes = $orderPayload->custom_attributes ?: [];
        foreach ($customAttributes as $key => $customAttribute) {
            isset($customAttribute->value) && $orderData[$key] = $customAttribute->value;
        }
        $quoteService->setOrderData($orderData);
        $quoteService->submitNominalItems();
        $quote->setIsActive(true);
        $order = $quoteService->submitOrder();
        self::saveExtOrderData($order, $orderPayload);
        Mage::dispatchEvent('checkout_type_onepage_save_order_after', ['order' => $order, 'quote' => $quote]);
        $paymentData = $orderPayload->payments ? current($orderPayload->payments) : null;
        if ($paymentData) {
            $payment = Bold_Checkout_Service_Hydrator_OrderPayment::hydrate($paymentData, $order);
            $payment->save();
        }
        $quote->save();
        if ($order->getCanSendNewEmailFlag()) {
            try {
                method_exists($order, 'queueNewOrderEmail')
                    ? $order->queueNewOrderEmail()
                    : $order->sendNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        $profiles = $quoteService->getRecurringPaymentProfiles();
        Mage::dispatchEvent(
            'checkout_submit_all_after',
            ['order' => $order, 'quote' => $quote, 'recurring_profiles' => $profiles]
        );

        return $order;
    }

    /**
     * Save order extension data.
     *
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $orderData
     * @return void
     * @throws Exception
     */
    private static function saveExtOrderData(Mage_Sales_Model_Order $order, stdClass $orderData)
    {
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->setOrderId($order->getEntityId());
        $extOrderData->setPublicId($orderData->custom_attributes->bold_cashier_public_order_id->value);
        $extOrderData->setFinancialStatus($orderData->financial_status);
        $extOrderData->setFulfillmentStatus($orderData->fulfillment_status);
        Mage::dispatchEvent(
            'bold_checkout_order_ext_data_save_before',
            [
                'order' => $order,
                'order_data' => $orderData,
                'order_ext_data' => $extOrderData
            ]
        );
        $extOrderData->save();
    }
}
