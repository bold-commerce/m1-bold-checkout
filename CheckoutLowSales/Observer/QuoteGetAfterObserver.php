<?php

/**
 * Observer for bold_checkout_get_quote_after event.
 */
class Bold_CheckoutLowSales_Observer_QuoteGetAfterObserver
{
    /**
     * Replace sessions as 'low_shipping' shipping method calculates cost using sessions.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function replaceSessionQuote(Varien_Event_Observer $event)
    {
        $quote = $event->getQuote();
        Mage::getSingleton('checkout/session')->replaceQuote($quote);
        Mage::getSingleton('customer/session')->setCustomer($quote->getCustomer());
    }
}
