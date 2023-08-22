<?php

/**
 * Send tax exempt email observer.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_CheckoutTaxExempt_Observer_OrderCreateAfterObserver
{
    /**
     * Send tax exempt upload document e-mail.
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function processTaxExempt(Varien_Event_Observer $event)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $event->getOrder();
        $orderData = $event->getOrderData();
        $taxExempt = isset($orderData->custom_attributes->_tax_exempt_checkbox_selected->value)
            ? $orderData->custom_attributes->_tax_exempt_checkbox_selected->value
            : null;
        if (!(bool)$taxExempt) {
            return;
        }
        /** @var Bold_CheckoutTaxExempt_Model_Config $config */
        $config = Mage::getSingleton(Bold_CheckoutTaxExempt_Model_Config::RESOURCE);
        $templateId = $config->getExemptEmailTemplate();
        if (!$templateId) {
            $message = Mage::helper('core')->__(
                'Please contact customer %s to obtain a tax-exempt certificate. ' .
                'The tax-exempt certificate upload email for order #%s has not' .
                ' been sent because the email template is not configured.',
                $order->getCustomerEmail(),
                $order->getId()
            );
            $order->addStatusHistoryComment($message);
            Mage::log($message, Zend_Log::ALERT);
            return;
        }
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        $uploadUrl = Mage::getUrl('sales/order/view/', ['order_id' => $order->getId()]);
        if ($order->getCustomerIsGuest()) {
            $uploadUrl = Mage::getUrl('checkouttaxexempt/index/view', ['order_id' => $extOrderData->getPublicId()]);
        }
        $vars = [
            'order' => $order,
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->getPaymentBlockHtml($order),
            'upload_url' => $uploadUrl . '#tax-exempt',
        ];
        Mage::getModel('core/email_template')->sendTransactional(
            $templateId,
            $config->getExemptEmailSender(),
            $order->getCustomerEmail(),
            $order->getCustomerName(),
            $vars
        );
    }

    /**
     * Get payment block html.
     *
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    private function getPaymentBlockHtml(Mage_Sales_Model_Order $order)
    {
        $storeId = $order->getStore()->getId();
        // Start store emulation process.
        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store).
            $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
                ->setIsSecureMode(true);
            $paymentBlock->getMethod()->setStore($storeId);
            $paymentBlockHtml = $paymentBlock->toHtml();
        } catch (\Exception $exception) {
            // Stop store emulation process.
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
            return '';
        }
        // Stop store emulation process.
        $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

        return $paymentBlockHtml;
    }
}
