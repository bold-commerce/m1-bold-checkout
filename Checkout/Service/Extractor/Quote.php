<?php

/**
 * Quote extractor.
 */
class Bold_Checkout_Service_Extractor_Quote
{
    /**
     * Extract quote data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return stdClass
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $result = new stdClass();
        $result->quote = self::extractQuote($quote);
        $result->totals = Bold_Checkout_Service_Extractor_Quote_Totals::extract($quote);
        $result->shipping_methods = Bold_Checkout_Service_Extractor_Quote_ShippingMethods::extract($quote);
        $result->errors = [];
        return $result;
    }

    /**
     * Extract quote entity data into array.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function extractQuote(Mage_Sales_Model_Quote $quote)
    {
        $billingAddress = Bold_Checkout_Service_Extractor_Quote_Address::extract($quote->getBillingAddress());
        $shippingAddress = Bold_Checkout_Service_Extractor_Quote_Address::extract($quote->getShippingAddress());
        $items = Bold_Checkout_Service_Extractor_Quote_Item::extract($quote);
        return [
            'id' => (int)$quote->getId(),
            'created_at' => $quote->getCreatedAt(),
            'updated_at' => $quote->getUpdatedAt(),
            'is_active' => (bool)$quote->getIsActive(),
            'is_virtual' => (bool)$quote->getIsVirtual(),
            'items' => $items,
            'items_count' => $quote->getItemsCount(),
            'items_qty' => $quote->getItemsQty(),
            'customer' => self::extractCustomer($quote),
            'billing_address' => $billingAddress,
            'orig_order_id' => (int)$quote->getOrigOrderId(),
            'currency' => [
                'global_currency_code' => $quote->getGlobalCurrencyCode(),
                'base_currency_code' => $quote->getBaseCurrencyCode(),
                'store_currency_code' => $quote->getStoreCurrencyCode(),
                'quote_currency_code' => $quote->getQuoteCurrencyCode(),
                'store_to_base_rate' => (float)$quote->getStoreToBaseRate(),
                'store_to_quote_rate' => (float)$quote->getStoreToQuoteRate(),
                'base_to_quote_rate' => (float)$quote->getBaseToQuoteRate(),
            ],
            'customer_is_guest' => (bool)$quote->getCustomerIsGuest(),
            'customer_note_notify' => (bool)$quote->getCustomerNoteNotify(),
            'customer_tax_class_id' => (int)$quote->getCustomerTaxClassId(),
            'store_id' => (int)$quote->getStoreId(),
            'extension_attributes' => [
                'shipping_assignments' => [
                    [
                        'shipping' => [
                            'address' => $shippingAddress,
                            'method' => $quote->getShippingAddress()->getShippingMethod(),
                        ],
                        'items' => $items,
                    ],
                ],
                'shipping_tax_amount' => (float)$quote->getShippingAddress()->getShippingTaxAmount(),
                'base_shipping_tax_amount' => (float)$quote->getShippingAddress()->getBaseShippingTaxAmount(),
            ],
        ];
    }

    /**
     * Extract customer data for the given quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    private static function extractCustomer(Mage_Sales_Model_Quote $quote)
    {
        $customer = $quote->getCustomer();
        if ($quote->getCustomerIsGuest()) {
            return [
                'email' => $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
            ];
        }
        return current(Bold_Checkout_Service_Extractor_Customer::extract([$customer]));
    }
}
