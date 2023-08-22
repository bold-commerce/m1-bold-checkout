<?php

/**
 * Bold taxes processor.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Api_Platform_Orders_TaxProcessor
{
    /**
     * Add bold taxes to quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param stdClass $orderData
     * @return void
     */
    public static function addTaxesToQuote(Mage_Sales_Model_Quote $quote, stdClass $orderData)
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress->getAppliedTaxes() || $shippingAddress->getShippingTaxAmount()) {
            return;
        }
        $baseCurrency = Mage::getModel('directory/currency')->load($quote->getBaseCurrencyCode());
        $quoteCurrency = Mage::getModel('directory/currency')->load($quote->getQuoteCurrencyCode());
        $baseTaxAmount = $orderData->shipping_tax;
        $appliedTaxes = [];
        $taxesForItems = [];
        foreach ($orderData->line_items as $lineItem) {
            $lineItemId = explode('-', $lineItem->cart_line_item_platform_id)[1];
            foreach ($lineItem->taxes as $tax) {
                $baseTaxAmount += $tax->amount;
                $taxCode = str_replace(' ', '_', strtolower($tax->name));
                $rates = [
                    [
                        'code' => $taxCode,
                        'title' => $tax->name,
                        'percent' => (float)$tax->rate,
                        'position' => '0',
                        'priority' => '0',
                        'rule_id' => null,
                    ],
                ];
                $taxesForItems[$lineItemId][] = [
                    'rates' => $rates,
                    'percent' => (float)$tax->rate,
                    'id' => $taxCode,
                ];
                $appliedTaxes[$taxCode] = [
                    'rates' => $rates,
                    'percent' => (float)$tax->rate,
                    'id' => $taxCode,
                    'process' => 0,
                    'amount' => $baseCurrency->convert((float)$tax->amount, $quoteCurrency),
                    'base_amount' => (float)$tax->amount,
                ];
            }
        }
        if (!$baseTaxAmount) {
            return;
        }
        $quote->setTaxesForItems($taxesForItems);
        $shippingAddress->setAppliedTaxes($appliedTaxes);
        $shippingAddress->setBaseShippingTaxAmount((float)$orderData->shipping_tax);
        $shippingAddress->setShippingTaxAmount(
            $baseCurrency->convert((float)$orderData->shipping_tax, $quoteCurrency)
        );
        $shippingAddress->setBaseTaxAmount($baseTaxAmount);
        $shippingAddress->setTaxAmount($baseCurrency->convert($baseTaxAmount, $quoteCurrency));
        $shippingAddress->setBaseSubtotalTotalInclTax($shippingAddress->getBaseSubtotal() + $baseTaxAmount);
        $shippingAddress->setSubtotalInclTax(
            $baseCurrency->convert($shippingAddress->getBaseSubtotal() + $baseTaxAmount, $quoteCurrency)
        );
        $shippingAddress->setBaseShippingInclTax(
            $shippingAddress->getBaseShippingAmount() + $shippingAddress->getBaseShippingTaxAmount()
        );
        $shippingAddress->setShippingInclTax(
            $shippingAddress->getShippingAmount() + $shippingAddress->getShippingTaxAmount()
        );
        $shippingAddress->setBaseGrandTotal((float)$orderData->total);
        $shippingAddress->setGrandTotal($baseCurrency->convert((float)$orderData->total, $quoteCurrency));
        $quote->setBaseGrandTotal((float)$orderData->total);
        $quote->setGrandTotal($baseCurrency->convert((float)$orderData->total, $quoteCurrency));
    }
}
