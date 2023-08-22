<?php

/**
 * Save order tax exempt observer.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_CheckoutTaxExempt_Observer_OrderExtDataSaveBeforeObserver
{
    /**
     * Set order tax exempt before save ext order data.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function setTaxExempt(Varien_Event_Observer $event)
    {
        $orderExtData = $event->getOrderExtData();
        $orderData = $event->getOrderData();
        $taxExempt = isset($orderData->custom_attributes->_tax_exempt_checkbox_selected->value)
            ? $orderData->custom_attributes->_tax_exempt_checkbox_selected->value
            : null;
        if (!(bool)$taxExempt) {
            return;
        }
        $orderExtData->setIsTaxExempt(1);
    }
}
