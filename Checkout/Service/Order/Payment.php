<?php

/**
 * Order payment service.
 */
class Bold_Checkout_Service_Order_Payment
{
    /**
     * Process payment.
     *
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $payment
     * @param null|stdClass $transaction
     * @return void
     * @throws Mage_Core_Exception
     */
    public static function processPayment(
        Mage_Sales_Model_Order $order,
        stdClass $payment,
        stdClass $transaction = null
    ) {
        $orderPayment = $order->getPayment();
        foreach ($payment as $key => $value) {
            if ($key === 'cc_last_4') {
                $orderPayment->setCcLast4($orderPayment->encrypt($value));
            }
            $orderPayment->setData($key, $value);
        }
        $baseAmountOrdered = isset($payment->base_amount_ordered) ? $payment->base_amount_ordered : null;
        $amountOrdered = isset($payment->amount_ordered) ? $payment->amount_ordered : null;
        $baseAmountAuthorized = isset($payment->base_amount_authorized) ? $payment->base_amount_authorized : null;
        $amountAuthorized = isset($payment->amount_authorized) ? $payment->amount_authorized : null;
        $baseAmountPaid = isset($payment->base_amount_paid) ? $payment->base_amount_paid : null;
        $amountPaid = isset($payment->amount_paid) ? $payment->amount_paid : null;
        $baseAmountOrdered = !$baseAmountOrdered && $amountOrdered ? $order->getOrderCurrency()->convert(
            $amountOrdered,
            $order->getBaseCurrency()
        ) : $baseAmountOrdered;
        $amountOrdered = !$amountOrdered && $baseAmountOrdered ? $order->getBaseCurrency()->convert(
            $baseAmountOrdered,
            $order->getOrderCurrency()
        ) : $amountOrdered;
        $baseAmountAuthorized = !$baseAmountAuthorized && $amountAuthorized
            ? $order->getOrderCurrency()->convert(
                $amountAuthorized,
                $order->getBaseCurrency()
            ) : $baseAmountAuthorized;
        $amountAuthorized = !$amountAuthorized && $baseAmountAuthorized
            ? $order->getBaseCurrency()->convert(
                $baseAmountAuthorized,
                $order->getOrderCurrency()
            ) : $amountAuthorized;
        $baseAmountPaid = !$baseAmountPaid && $amountPaid
            ? $order->getOrderCurrency()->convert(
                $amountPaid,
                $order->getBaseCurrency()
            ) : $baseAmountPaid;
        $amountPaid = !$amountPaid && $baseAmountPaid
            ? $order->getBaseCurrency()->convert(
                $baseAmountPaid,
                $order->getOrderCurrency()
            ) : $amountPaid;
        $baseAmountOrdered && $orderPayment->setBaseAmountOrdered($baseAmountOrdered);
        $amountOrdered && $orderPayment->setAmountOrdered($amountOrdered);
        $baseAmountAuthorized && $orderPayment->setBaseAmountAuthorized($baseAmountAuthorized);
        if ($amountAuthorized || $amountOrdered) {
            $orderPayment->setAmountAuthorized($amountAuthorized ?: $amountOrdered);
        }
        $baseAmountPaid && $orderPayment->setBaseAmountPaid($baseAmountPaid);
        $amountPaid && $orderPayment->setAmountPaid($amountPaid);
        $additionalInformation = isset($payment->extension_attributes->additional_information)
            ? $payment->extension_attributes->additional_information
            : [];
        foreach ($additionalInformation as $key => $value) {
            $orderPayment->setAdditionalInformation($key, $value);
        }
        if ($transaction) {
            $orderPayment->setTransactionId($transaction->txn_id);
            $transaction = $orderPayment->addTransaction($transaction->txn_type);
            if (!$orderPayment->getIsTransactionClosed()) {
                $transaction->setIsClosed(0);
            }
            $transaction->save();
        }
        $orderPayment->save();
    }
}
