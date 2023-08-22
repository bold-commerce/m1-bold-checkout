<?php

/**
 * Order payment entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order_Payment
{
    /**
     * Extract order payment entity data into array.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param array $transactions
     * @return array
     */
    public static function extract(
        Mage_Sales_Model_Order_Payment $payment,
        array $transactions = []
    ) {
        $transaction = $payment->getTransaction($payment->getLastTransId()) ?: $payment->getAuthorizationTransaction();
        if ($transaction) {
            $transactions[] = $transaction;
        }
        $result = [
            'platform_id' => (string)$payment->getId(),
            'currency' => (string)$payment->getOrder()->getBaseCurrencyCode(),
            'amount_planned' => (string)$payment->getBaseAmountOrdered(),
            'payment_method' => (string)$payment->getAdditionalInformation('payment_method'),
            'status' => (string)$payment->getAdditionalInformation('status'),
            'provider' => (string)$payment->getAdditionalInformation('provider'),
            'description' => (string)$payment->getAdditionalInformation('description'),
        ];
        if ($transactions) {
            $result['transactions'] = self::extractTransactions($transactions);
        }
        if ($payment->getAdditionalInformation()) {
            $result['custom_attributes'] = self::extractCustomAttributes($payment->getAdditionalInformation());
        }

        return $result;
    }

    /**
     * Extract order custom attributes.
     *
     * @param array $additionalInformation
     * @return array
     */
    private static function extractCustomAttributes(array $additionalInformation)
    {
        $customAttributes = [];
        $fieldsExempts = ['payment_method', 'status', 'provider', 'description'];
        foreach ($additionalInformation as $propertyName => $value) {
            if (in_array($propertyName, $fieldsExempts)) {
                continue;
            }
            $customAttributes[$propertyName] = [
                'description' => '',
                'value' => $value,
            ];
        }
        return $customAttributes;
    }

    /**
     * Extract payment transaction data.
     *
     * @param array $transactions
     * @return array
     */
    private static function extractTransactions(array $transactions)
    {
        $result = [];
        foreach ($transactions as $transaction) {
            $result[] = [
                'platform_id' => (string)$transaction->getId(),
                'provider_transaction_id' => (string)$transaction->getTxnId(),
                'amount' => (string)$transaction->getAdditionalInformation('amount'),
                'status' => (string)$transaction->getAdditionalInformation('status'),
                'currency' => (string)$transaction->getOrderPaymentObject()->getOrder()->getBaseCurrencyCode(),
                'type' => Bold_Checkout_Service_TransactionType::getBoldTransactionType($transaction->getTxnType()),
            ];
        }
        return $result;
    }
}
