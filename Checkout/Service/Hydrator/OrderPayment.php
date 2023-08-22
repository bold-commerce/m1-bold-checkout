<?php

/**
 * Hydrate Order Payment data service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_Hydrator_OrderPayment
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'amount_planned',
        'transactions',
        'description',
        'payment_method',
        'provider',
        'status',
    ];

    /**
     * Hydrate Order Payment data.
     *
     * @param stdClass $paymentPayload
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Payment
     * @throws Mage_Core_Exception
     * @throws Exception
     */
    public static function hydrate(
        stdClass $paymentPayload,
        Mage_Sales_Model_Order $order
    ) {
        Bold_Checkout_Service_PayloadValidator::validate($paymentPayload, self::$requiredFields);
        $orderPayment = $order->getPayment();
        $orderPayment->setBaseAmountOrdered((float)$paymentPayload->amount_planned);
        $orderPayment->setAmountOrdered(
            $order->getBaseCurrency()->convert(
                (float)$paymentPayload->amount_planned,
                $order->getOrderCurrency()
            )
        );
        $amountAuthorized = self::getAmountAuthorized($paymentPayload);
        $orderPayment->setBaseAmountAuthorized($amountAuthorized);
        $orderPayment->setAmountAuthorized(
            $order->getBaseCurrency()->convert($amountAuthorized, $order->getOrderCurrency())
        );
        $amountPaid = self::getAmountPaid($paymentPayload);
        $orderPayment->setBaseAmountPaid($amountPaid);
        $orderPayment->setAmountPaid(
            $order->getBaseCurrency()->convert($amountPaid, $order->getOrderCurrency())
        );
        $additionalInfo = isset($paymentPayload->custom_attributes) ? $paymentPayload->custom_attributes : [];
        foreach ($additionalInfo as $name => $info) {
            $orderPayment->setAdditionalInformation($name, $info->value);
        }
        $orderPayment->setAdditionalInformation('description', $paymentPayload->description);
        $orderPayment->setAdditionalInformation('payment_method', $paymentPayload->payment_method);
        $orderPayment->setAdditionalInformation('provider', $paymentPayload->provider);
        $orderPayment->setAdditionalInformation('status', $paymentPayload->status);
        $orderPayment->setMethod('bold');
        $transactionsData = $paymentPayload->transactions ?: [];
        $orderPaymentTransactions = [];
        foreach ($transactionsData as $transactionData) {
            $orderPaymentTransactions[] = Bold_Checkout_Service_Hydrator_OrderPaymentTransaction::hydrate(
                $transactionData
            );
        }
        $orderPayment->setTransactions($orderPaymentTransactions);

        return $orderPayment;
    }

    /**
     * Calculate amount authorized.
     *
     * @param stdClass $paymentData
     * @return float
     */
    private static function getAmountAuthorized(stdClass $paymentData)
    {
        $amountAuthorized = 0.0;
        $transactions = $paymentData->transactions ?: [];
        $authorizeTransactions = [];
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'authorization' && $transaction->status !== 'failure') {
                $authorizeTransactions[] = $transaction;
            }
        }
        if ($authorizeTransactions) {
            foreach ($authorizeTransactions as $authorizeTransaction) {
                $amountAuthorized += $authorizeTransaction->amount;
            }
            return $amountAuthorized;
        }
        $captureTransactions = [];
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'charge' && $transaction->status !== 'failure') {
                $captureTransactions[] = $transaction;
            }
        }
        foreach ($captureTransactions as $captureTransaction) {
            $amountAuthorized += $captureTransaction->amount;
        }

        return $amountAuthorized;
    }

    /**
     * Calculate paid amount.
     *
     * @param stdClass $paymentData
     * @return float
     */
    private static function getAmountPaid(stdClass $paymentData)
    {
        if ($paymentData->status === 'paid') {
            return $paymentData->amount_planned;
        }
        $transactions = $paymentData->transactions ?: [];
        $amountPaid = 0.0;
        $captureTransactions = [];
        foreach ($transactions as $transaction) {
            if ($transaction->type === 'charge' && $transaction->status !== 'failure') {
                $captureTransactions[] = $transaction;
            }
        }
        foreach ($captureTransactions as $captureTransaction) {
            $amountPaid += $captureTransaction->amount;
        }

        return $amountPaid;
    }
}
