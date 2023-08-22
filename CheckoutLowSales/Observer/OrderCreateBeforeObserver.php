<?php

/**
 * Observer for bold_checkout_order_create_before event.
 */
class Bold_CheckoutLowSales_Observer_OrderCreateBeforeObserver
{
    const TAX_EXEMPT_FIELD = 'tm_field1';
    const TAX_EXEMPT_VALUE = 'Click here if you are Tax Exempt';

    /**
     * Set tax exempt field.
     *
     * @param \Varien_Event_Observer $event
     * @return void
     */
    public function setTaxExempt(Varien_Event_Observer $event)
    {
        $orderData = $event->getOrderData();
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $event->getQuote();
        // phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
        $taxExempt = isset($orderData->custom_attributes->_tax_exempt_checkbox_selected->value)
            ? $orderData->custom_attributes->_tax_exempt_checkbox_selected->value
            : null;
        // phpcs:enable Zend.NamingConventions.ValidVariableName.NotCamelCaps
        if ($taxExempt === 'true') {
            $quote->setData(self::TAX_EXEMPT_FIELD, self::TAX_EXEMPT_VALUE);
        }
    }
}
