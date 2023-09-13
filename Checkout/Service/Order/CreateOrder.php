<?php

/**
 * Order create service.
 */
class Bold_Checkout_Service_Order_CreateOrder
{
    /**
     * Create order.
     *
     * @param stdClass $orderPayload
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Exception
     */
    public static function create(stdClass $orderPayload, Mage_Sales_Model_Quote $quote)
    {
        self::prepareStore($quote);
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($orderPayload->order->publicId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
        $order = Mage::getModel('sales/order');
        $order->load($extOrderData->getOrderId());
        if ($order->getId()) {
            return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
        }
        if (!$quote->getIsActive()) {
            Mage::throwException(Mage::helper('checkout')->__('Quote "%s" is not active', $quote->getId()));
        }
        Mage::dispatchEvent(
            'bold_checkout_order_create_before',
            [
                'quote' => $quote,
                'order_data' => $orderPayload,
            ]
        );
        $order = self::createOrderFromQuote($quote, $orderPayload->order);
        self::saveExtOrderData($order, $orderPayload);
        if (round($quote->getBaseGrandTotal(), 2) - round($orderPayload->order->total, 2)) {
            self::addCommentToOrder($order, $orderPayload);
        }
        Mage::dispatchEvent(
            'bold_checkout_order_create_after',
            [
                'order_data' => $orderPayload,
                'order' => $order,
            ]
        );
        return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
    }

    /**
     * Place order from quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param stdClass $orderPayload
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    private static function createOrderFromQuote(Mage_Sales_Model_Quote $quote, stdClass $orderPayload)
    {
        $orderData = ['ext_order_id' => $orderPayload->orderNumber, 'remote_ip' => $orderPayload->browserIp];
        $customAttributes = isset($orderPayload->extension_attributes->note_attributes)
            ? $orderPayload->extension_attributes->note_attributes
            : [];
        foreach ($customAttributes as $key => $customAttribute) {
            isset($customAttribute->value) && $orderData[$key] = $customAttribute->value;
        }
        self::prepareQuote($quote);
        /** @var Mage_Sales_Model_Service_Quote $quoteService */
        $quoteService = Mage::getModel('sales/service_quote', $quote);
        $quoteService->setOrderData($orderData);
        $quoteService->submitNominalItems();
        $order = $quoteService->submitOrder();
        $quote->save();
        Bold_Checkout_Service_Order_Payment::processPayment(
            $order,
            $orderPayload->payment,
            $orderPayload->transaction
        );
        Mage::dispatchEvent('checkout_type_onepage_save_order_after', ['order' => $order, 'quote' => $quote]);
        if ($order->getCanSendNewEmailFlag()) {
            try {
                method_exists($order, 'queueNewOrderEmail')
                    ? $order->queueNewOrderEmail()
                    : $order->sendNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        $profiles = $quoteService->getRecurringPaymentProfiles();
        Mage::dispatchEvent(
            'checkout_submit_all_after',
            ['order' => $order, 'quote' => $quote, 'recurring_profiles' => $profiles]
        );

        return $order;
    }

    /**
     * Add customer and customer address data to cart.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     */
    private static function prepareQuoteForCustomer(Mage_Sales_Model_Quote $quote)
    {
        if (!$quote->getCustomerId()) {
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
            return;
        }
        $billing = $quote->getBillingAddress();
        $billing->setCustomerId($quote->getCustomerId());
        $shipping = $quote->isVirtual() ? null : $quote->getShippingAddress();
        if ($shipping) {
            $shipping->setCustomerId($quote->getCustomerId());
        }
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($quote->getCustomerId());
        $hasDefaultBilling = (bool)$customer->getDefaultBilling();
        $hasDefaultShipping = (bool)$customer->getDefaultShipping();
        if ($shipping && !$shipping->getSameAsBilling() &&
            (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
        ) {
            $shippingAddress = $shipping->exportCustomerAddress();
            if (!$hasDefaultShipping) {
                //Make provided address as default shipping address
                $shippingAddress->setIsDefaultShipping(true);
                $hasDefaultShipping = true;
            }
            $quote->addCustomerAddress($shippingAddress);
            $shipping->setCustomerAddressData($shippingAddress);
        }
        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $billingAddress = $billing->exportCustomerAddress();
            if (!$hasDefaultBilling) {
                if (!$hasDefaultShipping) {
                    $billingAddress->setIsDefaultShipping(true);
                }
                $billingAddress->setIsDefaultBilling(true);
            }
            $quote->addCustomerAddress($billingAddress);
            $billing->setCustomerAddressData($billingAddress);
        }
    }

    /**
     * Save order extension data.
     *
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $orderData
     * @return void
     * @throws Exception
     */
    private static function saveExtOrderData(Mage_Sales_Model_Order $order, stdClass $orderData)
    {
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->setOrderId($order->getEntityId());
        $extOrderData->setPublicId($orderData->order->publicId);
        $extOrderData->setFinancialStatus($orderData->order->financialStatus);
        $extOrderData->setFulfillmentStatus($orderData->order->fulfillmentStatus);
        Mage::dispatchEvent(
            'bold_checkout_order_ext_data_save_before',
            [
                'order' => $order,
                'order_data' => $orderData,
                'order_ext_data' => $extOrderData,
            ]
        );
        $extOrderData->save();
    }

    /**
     * Prepare quote for order creation.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     */
    private static function prepareQuote(Mage_Sales_Model_Quote $quote)
    {
        $quote->setIsActive(true);
        $quote->getPayment()->setMethod(Bold_Checkout_Service_PaymentMethod::CODE);
        $quote->getPayment()->setStoreId($quote->getStoreId());
        $quote->getPayment()->setCustomerPaymentId($quote->getCustomerId());
        self::prepareQuoteForCustomer($quote);
        if (!$quote->isVirtual()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
    }

    /**
     * Add comment to order in case it's quote total is different from bold order total.
     *
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $orderData
     * @return void
     * @throws Exception
     */
    private static function addCommentToOrder(
        Mage_Sales_Model_Order $order,
        stdClass $orderData
    ) {
        $comment = Mage::helper('core')->__(
            'Please consider to cancel/refund this order due to it\'s total = %s mismatch transaction amount = %s. '
            . 'For more details please refer to Bold Help Center at "https://support.boldcommerce.com"',
            $order->getBaseGrandTotal(),
            $orderData->order->total
        );
        $order->addStatusHistoryComment($comment);
        $order->save();
    }

    /**
     * Prepare store for the given quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return void
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function prepareStore(Mage_Sales_Model_Quote $quote)
    {
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        Mage::app()->setCurrentStore($quote->getStoreId());
        Mage::app()->getStore()->setCurrentCurrencyCode($quote->getQuoteCurrencyCode());
        /** @var Mage_Checkout_Model_Session $checkoutSession */
        $checkoutSession = Mage::getSingleton('checkout/session');
        $checkoutSession->setQuoteId($quote->getId());
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        $customerSession->setCustomerId($quote->getCustomerId());
    }
}
