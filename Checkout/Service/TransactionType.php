<?php

/**
 * Payment transaction type service.
 */
class Bold_Checkout_Service_TransactionType
{
    /**
     * Transactions map.
     *
     * @var array
     */
    private static $types = [
        'authorization' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
        'charge' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
        'refund' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND,
    ];

    /**
     * Retrieve corresponding magento transaction type.
     *
     * @param string $type
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getPlatformTransactionType($type)
    {
        if (!isset(self::$types[$type])) {
            throw new InvalidArgumentException(sprintf('Unsupported transaction type "%s".', $type));
        }

        return self::$types[$type];
    }

    /**
     * Retrieve bold transaction type.
     *
     * @param string $type
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getBoldTransactionType($type)
    {
        // There is no void type on bold side.
        if ($type === Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID) {
            return 'authorization';
        }
        $publicType = array_search($type, self::$types);
        if ($publicType === false) {
            throw new InvalidArgumentException(sprintf('Unsupported transaction type "%s".', $type));
        }

        return $publicType;
    }
}
