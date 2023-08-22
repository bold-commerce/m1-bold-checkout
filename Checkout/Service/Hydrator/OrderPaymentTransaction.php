<?php

/**
 * Hydrate Order Payment Transaction data service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_Hydrator_OrderPaymentTransaction
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'provider_transaction_id',
        'amount',
        'status',
    ];

    /**
     * Hydrate Order Payment Transaction data.
     *
     * @param stdClass $dataSource
     * @return Mage_Sales_Model_Order_Payment_Transaction
     * @throws Mage_Core_Exception
     */
    public static function hydrate(stdClass $dataSource)
    {
        Bold_Checkout_Service_PayloadValidator::validate($dataSource, self::$requiredFields);
        /** @var Mage_Sales_Model_Order_Payment_Transaction $orderTransaction */
        $orderTransaction = Mage::getModel('sales/order_payment_transaction');
        $orderTransaction->setTxnType(
            Bold_Checkout_Service_TransactionType::getPlatformTransactionType($dataSource->type)
        );
        $orderTransaction->setTxnId($dataSource->provider_transaction_id);
        $orderTransaction->setAdditionalInformation('amount', $dataSource->amount);
        $orderTransaction->setAdditionalInformation('status', $dataSource->status);
        $dataSource->status === 'failure'
            ? $orderTransaction->setIsClosed(1) : $orderTransaction->setIsClosed(0);

        return $orderTransaction;
    }
}
