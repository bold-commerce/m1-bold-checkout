<?php

/**
 * Add fee to cart action.
 */
class Bold_CheckoutSmartwaveOnepageCheckout_Service_Action_AddFeeToCart
    implements Bold_Checkout_Service_Action_QuoteActionInterface
{
    const TYPE = 'add_fee';
    const FEE_CODE = 'mageworx_multifees';

    /**
     * @var string[]
     */
    private static $requiredFields = [
        'title',
        'tax_class_id',
        'base_price',
    ];

    /**
     * Add mageworx multi-fee to cart if available.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array[]
     */
    public static function generateActionData(Mage_Sales_Model_Quote $quote)
    {
        $result = [];
        foreach ($quote->getTotals() as $code => $total) {
            if ($code !== self::FEE_CODE) {
                continue;
            }
            $fullInfo = Mage::helper('core/unserializeArray')->unserialize($total->getFullInfo()) ?: [];
            foreach ($fullInfo as $fee) {
                if (self::skipFee($fee)) {
                    continue;
                }
                $title = $fee['title'] . ': ';
                $options = isset($fee['options']) ? $fee['options'] : [];
                foreach ($options as $option) {
                    $title .= $option['title'] . ', ';
                }
                $title = rtrim($title, ': ');
                $title = rtrim($title, ', ');
                $result[] = [
                    'type' => self::TYPE,
                    'data' => [
                        'id' => self::FEE_CODE,
                        'line_text' => $title,
                        'fee_type' => 'fixed',
                        'taxable' => (int)$fee['tax_class_id'] === 6,
                        'value' => $fee['base_price'],
                        'is_amendment' => false,
                    ],
                ];
            }
        }
        return $result;
    }

    /**
     * Verify if fee has all necessary data.
     *
     * @param array $fee
     * @return bool
     */
    private static function skipFee(array $fee)
    {
        foreach (self::$requiredFields as $requiredField) {
            if (!isset($fee[$requiredField])) {
                return true;
            }
        }

        return false;
    }
}
