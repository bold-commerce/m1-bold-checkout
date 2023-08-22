<?php

/**
 * Platform payments api service.
 */
class Bold_Checkout_Api_Platform_OrderPayments
{
    /**
     * Update payment information.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
     */
    public static function update(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/payments\/(\d*)/', $request->getRequestUri(), $paymentIdMatches);
        $paymentId = isset($paymentIdMatches[1]) ? $paymentIdMatches[1] : null;
        if (!$paymentId) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify payment id in request.',
                400,
                'server.validation_error'
            );
        }
        preg_match('/orders\/(.*?)\/payments/', $request->getRequestUri(), $orderIdMatches);
        $orderId = isset($orderIdMatches[1]) ? $orderIdMatches[1] : null;
        if (!$orderId) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify order id in request.',
                400,
                'server.validation_error'
            );
        }
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = Mage::getModel('sales/order_payment')->load($paymentId);
        if (!$payment->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Could not find payment with id: %s', $paymentId),
                409,
                'server.validation_error'
            );
        }
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        if (!$order->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Could not find order with id: %s', $paymentId),
                409,
                'server.validation_error'
            );
        }
        $requestBody = json_decode($request->getRawBody());
        try {
            if ($requestBody->data->payment->status === 'paid' && !$order->getTotalInvoiced()) {
                self::invoice($order, $payment, $requestBody);
            }
            $paymentData = self::getPaymentData($payment, $requestBody, $order);
        } catch (Mage_Core_Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                [
                    'data' => [
                        'payment' => Bold_Checkout_Service_Extractor_Order_Payment::extract($payment, $paymentData),
                    ],
                ]
            )
        );
    }

    /**
     * Build payment data.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param stdClass $requestBody
     * @param Mage_Sales_Model_Order $order
     * @return array
     * @throws Mage_Core_Exception
     */
    private static function getPaymentData(
        Mage_Sales_Model_Order_Payment $payment,
        stdClass $requestBody,
        Mage_Sales_Model_Order $order
    ) {
        $payment->setAdditionalInformation('status', $requestBody->data->payment->status);
        $payment->setOrder($order);
        $paymentData = [];
        foreach ($requestBody->data->payment->transactions as $requestTransaction) {
            /** @var Mage_Sales_Model_Order_Payment_Transaction $transaction */
            $transaction = Mage::getModel('sales/order_payment_transaction');
            $platformId = $requestTransaction->platform_id
                ?: $payment->getTransaction($payment->getLastTransId())->getId();
            $transaction->setId($platformId);
            $txnId = $requestTransaction->provider_transaction_id ?: $payment->getLastTransId();
            $transaction->setTxnId($txnId);
            $transaction->setTxnType(
                Bold_Checkout_Service_TransactionType::getPlatformTransactionType($requestTransaction->type)
            );
            $transaction->setAdditionalInformation('amount', $requestTransaction->amount);
            $transaction->setAdditionalInformation('status', $requestTransaction->status);
            $transaction->setOrderPaymentObject($payment);
            $paymentData[] = $transaction;
        }
        return $paymentData;
    }

    /**
     * Create invoice for order.
     *
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param stdClass $requestBody
     * @return void
     * @throws Mage_Core_Exception
     */
    private static function invoice(
        Mage_Sales_Model_Order $order,
        Mage_Sales_Model_Order_Payment $payment,
        stdClass $requestBody
    ) {
        /** @var Bold_Checkout_Model_Order $orderExtensionData */
        $orderExtensionData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $orderExtensionData->load($order->getEntityId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        if ($orderExtensionData->getIsDelayedCapture()) {
            return;
        }
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase('offline');
        $invoice->register();
        $invoice->setEmailSent(true);
        $invoice->setTransactionId($payment->getLastTransId());
        $invoice->getOrder()->setCustomerNoteNotify(true);
        $invoice->getOrder()->setIsInProcess(true);
        $transactions = self::getPaymentData($payment, $requestBody, $order);
        $resourceTransaction = Mage::getModel('core/resource_transaction');
        $resourceTransaction->addObject($invoice);
        $resourceTransaction->addObject($invoice->getOrder());
        $resourceTransaction->addObject($payment);
        foreach ($transactions as $transaction) {
            $resourceTransaction->addObject($transaction);
        }
        $resourceTransaction->save();
    }
}
