<?php

/**
 * Extract quote totals segments.
 */
class Bold_Checkout_Service_Extractor_Quote_Totals_Segment
{
    /**
     * Extract quote totals segments.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $segments = [];
        foreach ($quote->getTotals() as $total) {
            $segments[] = self::extractSegment($total);
        }
        return $segments;
    }

    /**
     * Extract quote totals segment entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Address_Total $total
     * @return array
     */
    private static function extractSegment($total)
    {
        $segment =  [
            'code' => $total->getCode(),
            'title' => $total->getTitle(),
            'value' => (float)$total->getValue(),
        ];
        if ($total->getCode() === 'tax') {
            $segment['extension_attributes']['tax_grandtotal_details'] = self::extractTaxGrandtotalDetails($total);
        }
        return $segment;
    }
    /**
     * Extract tax grand total details.
     *
     * @param Mage_Sales_Model_Quote_Address_Total $total
     * @return array
     */
    private static function extractTaxGrandtotalDetails($total)
    {
        $taxGrandTotalDetails = [];
        foreach ($total->getFullInfo() as $info) {
            $taxGrandTotalDetails[] = [
                'amount' => Mage::app()->getStore()->roundPrice($info['amount']),
                'rates' => $info['rates'],
                'base_amount' => Mage::app()->getStore()->roundPrice($info['base_amount']),
            ];
        }
        return $taxGrandTotalDetails;
    }
}
