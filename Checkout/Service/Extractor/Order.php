<?php

/**
 * Order entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Order
{
    /**
     * Extract orders data.
     *
     * @param Mage_Sales_Model_Order[] $orders
     * @return array
     */
    public static function extract(array $orders)
    {
        $result = [];
        foreach ($orders as $order) {
            $result[] = self::extractOrder($order);
        }

        return $result;
    }

    /**
     * Extract order entity data into array.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public static function extractOrder(Mage_Sales_Model_Order $order)
    {
        $items = Bold_Checkout_Service_Extractor_Order_Item::extract($order);
        $orderExtData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $orderExtData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        $billingAddress = Bold_Checkout_Service_Extractor_Order_Address::extract($order->getBillingAddress());
        $shippingAddress = $order->getIsVirtual()
            ? null
            : Bold_Checkout_Service_Extractor_Order_Address::extract($order->getShippingAddress());
        $payment = Bold_Checkout_Service_Extractor_Order_Payment::extract($order->getPayment());
        $appliedTaxes = Bold_Checkout_Service_Extractor_Order_Tax::extractAppliedTaxes($order);
        $itemAppliedTaxes = Bold_Checkout_Service_Extractor_Order_Tax::extractItemAppliedTaxes($order);
        return [
            'applied_rule_ids' => (string)$order->getAppliedRuleIds(),
            'base_currency_code' => $order->getBaseCurrencyCode(),
            'base_discount_amount' => (float)$order->getBaseDiscountAmount(),
            'base_grand_total' => (float)$order->getBaseGrandTotal(),
            'base_discount_tax_compensation_amount' => 0,
            'base_shipping_amount' => (float)$order->getBaseShippingAmount(),
            'base_shipping_discount_amount' => (float)$order->getBaseShippingDiscountAmount(),
            'base_shipping_discount_tax_compensation_amnt' => 0,
            'base_shipping_incl_tax' => (float)$order->getBaseShippingInclTax(),
            'base_shipping_tax_amount' => (float)$order->getBaseShippingTaxAmount(),
            'base_subtotal' => (float)$order->getBaseSubtotal(),
            'base_subtotal_incl_tax' => (float)$order->getBaseSubtotalInclTax(),
            'base_tax_amount' => (float)$order->getBaseTaxAmount(),
            'base_total_due' => (float)$order->getBaseTotalDue(),
            'base_to_global_rate' => (float)$order->getBaseToGlobalRate(),
            'base_to_order_rate' => (float)$order->getBaseToOrderRate(),
            'billing_address_id' => (int)$order->getBillingAddressId(),
            'created_at' => $order->getCreatedAt(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_group_id' => (int)$order->getCustomerGroupId(),
            'customer_is_guest' => (int)$order->getCustomerIsGuest(),
            'customer_note_notify' => (int)$order->getCustomerNoteNotify(),
            'discount_amount' => (float)$order->getDiscountAmount(),
            'discount_description' => '',
            'email_sent' => (int)$order->getEmailSent(),
            'entity_id' => (int)$order->getId(),
            'ext_order_id' => (string)$order->getExtOrderId(),
            'global_currency_code' => $order->getGlobalCurrencyCode(),
            'grand_total' => (float)$order->getGrandTotal(),
            'discount_tax_compensation_amount' => 0,
            'increment_id' => $order->getIncrementId(),
            'is_virtual' => (int)$order->getIsVirtual(),
            'order_currency_code' => $order->getOrderCurrencyCode(),
            'protect_code' => $order->getProtectCode(),
            'quote_id' => (int)$order->getQuoteId(),
            'remote_ip' => $order->getRemoteIp(),
            'shipping_amount' => (float)$order->getShippingAmount(),
            'shipping_description' => $order->getShippingDescription(),
            'shipping_discount_amount' => (float)$order->getShippingDiscountAmount(),
            'shipping_discount_tax_compensation_amount' => 0,
            'shipping_incl_tax' => (float)$order->getShippingInclTax(),
            'shipping_tax_amount' => (float)$order->getShippingTaxAmount(),
            'state' => $order->getState(),
            'status' => $order->getStatus(),
            'store_currency_code' => $order->getStoreCurrencyCode(),
            'store_id' => (int)$order->getStoreId(),
            'store_name' => $order->getStoreName(),
            'store_to_base_rate' => (float)$order->getStoreToBaseRate(),
            'store_to_order_rate' => (float)$order->getStoreToOrderRate(),
            'subtotal' => (float)$order->getSubtotal(),
            'subtotal_incl_tax' => (float)$order->getSubtotalInclTax(),
            'tax_amount' => (float)$order->getTaxAmount(),
            'total_due' => (float)$order->getTotalDue(),
            'total_item_count' => (int)$order->getTotalItemCount(),
            'total_qty_ordered' => (int)$order->getTotalQtyOrdered(),
            'updated_at' => $order->getUpdatedAt(),
            'weight' => (float)$order->getWeight(),
            'items' => $items,
            'billing_address' => $billingAddress,
            'payment' => $payment,
            'status_histories' => [],
            'extension_attributes' => [
                'shipping_assignments' => [
                    [
                        'shipping' => [
                            'address' => $shippingAddress,
                            'method' => $order->getShippingMethod(),
                            'total' => [
                                'base_shipping_amount' => (float)$order->getBaseShippingAmount(),
                                'base_shipping_discount_amount' => (float)$order->getBaseShippingDiscountAmount(),
                                'base_shipping_discount_tax_compensation_amnt' => 0,
                                'base_shipping_incl_tax' => (float)$order->getBaseShippingInclTax(),
                                'base_shipping_tax_amount' => (float)$order->getBaseShippingTaxAmount(),
                                'shipping_amount' => (float)$order->getShippingAmount(),
                                'shipping_discount_amount' => (float)$order->getShippingDiscountAmount(),
                                'shipping_discount_tax_compensation_amount' => 0,
                                'shipping_incl_tax' => (float)$order->getShippingInclTax(),
                                'shipping_tax_amount' => (float)$order->getShippingTaxAmount(),
                            ],
                        ],
                        'items' => Bold_Checkout_Service_Extractor_Order_ShippingAssignmentItem::extract($order),
                    ],
                ],
                'applied_taxes' => $appliedTaxes,
                'item_applied_taxes' => $itemAppliedTaxes,
                'converting_from_quote' => true,
                'public_id' => $orderExtData->getPublicId(),
            ],
        ];
    }
}
