<?php

/**
 * Platform orders api service.
 *
 * phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Api_Platform_Orders
{
    /**
     * Retrieve order list created with bold checkout.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function getList(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $listBuilder = function ($limit, $cursor, $websiteId) {
            return Bold_Checkout_Model_Resource_OrderListBuilder::buildList($limit, $cursor, $websiteId);
        };
        try {
            return Bold_Checkout_Rest::buildListResponse($request, $response, 'orders', $listBuilder);
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
    }

    /**
     * Retrieve order by increment id.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function get(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/orders\/(.*)/', $request->getRequestUri(), $orderIdMatches);
        $orderId = isset($orderIdMatches[1]) ? $orderIdMatches[1] : null;
        if (!$orderId) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify order id in request.',
                400,
                'server.validation_error'
            );
        }
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        if (!$order->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Could not find order with id: %s', $orderId),
                409,
                'server.validation_error'
            );
        }
        $orderData = current(Bold_Checkout_Service_Extractor_Order::extract([$order], false));
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                [
                    'data' => [
                        'order' => $orderData,
                    ],
                ]
            )
        );
    }

    /**
     * Create and save in db magento order from request.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function create(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $requestBody = json_decode($request->getRawBody());
        $publicOrderId = isset($requestBody->data->order->custom_attributes->bold_cashier_public_order_id->value) ?
            $requestBody->data->order->custom_attributes->bold_cashier_public_order_id->value
            : null;
        if (!$publicOrderId) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please enable "Use Public Order Id" configuration in your bold app.',
                400,
                'server.validation_error'
            );
        }
        try {
            $orderData = $requestBody->data->order;
            $websiteId = self::getWebsiteId($orderData);
            /** @var Bold_Checkout_Model_Config $config */
            $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
            $order = $config->isCheckoutTypeSelfHosted($websiteId)
                ? self::processOrder($orderData, $publicOrderId)
                : self::createOrder($orderData, $publicOrderId);
            return Bold_Checkout_Rest::buildResponse(
                $response,
                json_encode(
                    ['data' => ['order' => $order]]
                ),
                201
            );
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf(
                    'Error occurred during order with public id = "%s" creation. %s',
                    $publicOrderId,
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Update order status.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function update(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        preg_match('/orders\/(.*)/', $request->getRequestUri(), $orderIdMatches);
        $orderId = isset($orderIdMatches[1]) ? $orderIdMatches[1] : null;
        if (!$orderId) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify order id in request.',
                400,
                'server.validation_error'
            );
        }
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($orderId);
        if (!$order->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Could not find order with id: %s', $orderId),
                409,
                'server.validation_error'
            );
        }
        $requestBody = json_decode($request->getRawBody());
        $orderData = isset($requestBody->data->order) ? $requestBody->data->order : null;
        $refundedAmount = isset($orderData->refunded_amount) ? $orderData->refunded_amount : null;
        if ($refundedAmount) {
            $order->setBaseTotalOnlineRefunded($refundedAmount);
        }
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($order->getId(), Bold_Checkout_Model_Resource_Order::ORDER_ID);
        if (!$extOrderData->getId()) {
            $extOrderData->setOrderId($order->getId());
        }
        $extOrderData->setFulfillmentStatus($orderData->fulfillment_status);
        $extOrderData->setFinancialStatus($orderData->financial_status);
        try {
            $extOrderData->save();
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        $orderData = current(Bold_Checkout_Service_Extractor_Order::extract([$order], false));
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                [
                    'data' => [
                        'order' => $orderData,
                    ],
                ]
            )
        );
    }

    /**
     * Retrieve and process order quote.
     *
     * @param stdClass $orderPayload
     * @return Mage_Sales_Model_Quote|null
     * @throws Mage_Core_Exception
     */
    private static function prepareOrderQuote(stdClass $orderPayload)
    {
        $quote = Bold_Checkout_Service_GetQuoteFromLineItems::getQuote($orderPayload->line_items);
        if (!$quote) {
            Mage::throwException(
                Mage::helper('core')->__(
                    'No active cart for order with public id = "%s" has been found.',
                    $orderPayload->custom_attributes->bold_cashier_public_order_id->value
                )
            );
        }
        $quote->getPayment()->setMethod(Bold_Checkout_Service_PaymentMethod::CODE);
        $quote->getPayment()->setStoreId($quote->getStoreId());
        $quote->getPayment()->setCustomerPaymentId($quote->getCustomerId());
        Bold_Checkout_Service_QuoteAddress::updateBillingAddress($orderPayload->billing_address, $quote);
        $shippingAddress = $orderPayload->shipping_addresses ? current($orderPayload->shipping_addresses) : null;
        if ($shippingAddress) {
            Bold_Checkout_Service_QuoteAddress::updateShippingAddress($shippingAddress, $quote);
            $quote->getShippingAddress()->setShippingMethod($orderPayload->shipping_code);
            $quote->getShippingAddress()->setShippingDescription($orderPayload->shipping_method);
        }
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getCustomerId()) {
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);
        }

        return $quote;
    }

    /**
     * Save order transactions.
     *
     * @param Mage_Sales_Model_Order $order
     * @return void
     * @throws Exception
     */
    private static function processTransactions(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment();
        if (!$payment) {
            return;
        }
        $transaction = self::getPaymentTransaction($payment);
        if (!$transaction) {
            return;
        }
        if (round($payment->getBaseAmountPaid(), 2) === round($order->getBaseGrandTotal(), 2)) {
            $transaction->setIsClosed(1);
        }
        $payment->setLastTransId($transaction->getTxnId());
        $transaction->setOrderPaymentObject($payment);
        $transaction->setOrder($order);
        $transaction->save();
        $payment->save();
    }

    /**
     * Save Customer Addresses based on Order data.
     *
     * @param Mage_Sales_Model_Order $order
     * @param stdClass $orderData
     * @return void
     * @throws Exception
     */
    private static function saveCustomerAddresses(Mage_Sales_Model_Order $order, stdClass $orderData)
    {
        $customerId = $order->getCustomerId();
        if (!empty($customerId)) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer');
            $customer->load($customerId);
            if ($customer->getId()) {
                $actualAddresses = Bold_Checkout_Service_Orders_AddressListProvider::getActualAddresses(
                    $customer->getAddresses(),
                    $orderData->billing_address,
                    $orderData->shipping_addresses
                );
                array_map(
                    function (Mage_Customer_Model_Address $address) use ($customer) {
                        !in_array($address, $customer->getAddresses()) && $customer->addAddress($address);
                    },
                    $actualAddresses
                );
                $customer->setDataChanges(true);
                $customer->save();
            }
        }
    }

    /**
     * Retrieve active payment transaction.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    private static function getPaymentTransaction(Mage_Sales_Model_Order_Payment $payment)
    {
        $transactions = $payment->getTransactions() ?: [];
        foreach ($transactions as $transaction) {
            $status = $transaction->getAdditionalInformation('status');
            if ($status && $status !== 'failure') {
                return $transaction;
            }
        }

        return null;
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
        $operation = $order->hasInvoices() ? 'refund' : 'cancel';
        $transactionType = $order->hasInvoices() ? 'payment' : 'authorization';
        $comment = Mage::helper('core')->__(
            'Please consider to %s this order due to it\'s total = %s mismatch %s transaction amount = %s. '
            . 'For more details please refer to Bold Help Center at "https://support.boldcommerce.com"',
            $operation,
            $order->getBaseGrandTotal(),
            $transactionType,
            $orderData->total
        );
        $order->addStatusHistoryComment($comment);
        $order->save();
        $message = Mage::helper('core')->__(
            'Magento Order Base Grand Total: "%s" mismatch Bold Order Grand Total: "%s. '
            . 'Magento Order Data: %s' . PHP_EOL . 'Bold Order Data: "%s"',
            $order->getBaseGrandTotal(),
            $orderData->total,
            $order->toJson(),
            json_encode($orderData)
        );
        Mage::log($message, Zend_Log::ERR);
    }

    /**
     * @param stdClass $orderData
     * @return int
     * @throws Mage_Core_Exception
     */
    private static function getWebsiteId(stdClass $orderData)
    {
        foreach ($orderData->line_items as $lineItem) {
            $storeId = isset($lineItem->custom_attributes->_store_id->value)
                ? $lineItem->custom_attributes->_store_id->value
                : null;
            if ($storeId) {
                //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
                $store = Mage::getModel('core/store')->load($storeId);
                if ($store->getId()) {
                    return $store->getWebsiteId();
                }
            }
        }
        return Mage::app()->getWebsite()->getId();
    }

    /**
     * @param stdClass $orderData
     * @param string $publicOrderId
     * @return array
     * @throws Mage_Core_Exception
     */
    private static function processOrder(stdClass $orderData, $publicOrderId)
    {
        $attempt = 1;
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        do {
            //phpcs:ignore MEQP1.Performance.Loop.ModelLSD
            $extOrderData->load($publicOrderId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
            if (!$extOrderData->getOrderId()) {
                $attempt++;
                //@phpcs:disable MEQP1.Security.DiscouragedFunction.Found
                sleep(1);
            }
        } while (!$extOrderData->getOrderId() && $attempt < 3);
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->load($extOrderData->getOrderId());
        if (!$order->getId()) {
            Mage::throwException('Order with public id ' . $publicOrderId . ' not found');
        }
        $paymentData = $orderData->payments ? current($orderData->payments) : null;
        if ($paymentData) {
            $payment = Bold_Checkout_Service_Hydrator_OrderPayment::hydrate($paymentData, $order);
            $payment->save();
        }
        self::processTransactions($order);
        if (round($order->getBaseGrandTotal(), 2) - round($orderData->total, 2)) {
            self::addCommentToOrder($order, $orderData);
        }
        $extOrderData->setFinancialStatus($orderData->financial_status);
        $extOrderData->setFulfillmentStatus($orderData->fulfillment_status);
        Mage::dispatchEvent(
            'bold_checkout_order_ext_data_save_before',
            [
                'order' => $order,
                'order_data' => $orderData,
                'order_ext_data' => $extOrderData,
            ]
        );
        $extOrderData->save();
        return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
    }

    /**
     * Create order from payload.
     *
     * @param stdClass $orderData
     * @param string $publicOrderId
     * @return array
     * @throws Mage_Core_Exception
     */
    private static function createOrder(stdClass $orderData, $publicOrderId)
    {
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        /** @var Bold_Checkout_Model_Order $extOrderData */
        $extOrderData = Mage::getModel(Bold_Checkout_Model_Order::RESOURCE);
        $extOrderData->load($publicOrderId, Bold_Checkout_Model_Resource_Order::PUBLIC_ID);
        $order = Mage::getModel('sales/order');
        $order->load($extOrderData->getOrderId());
        if ($order->getId()) {
            return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
        }
        $quote = self::prepareOrderQuote($orderData);
        Mage::dispatchEvent(
            'bold_checkout_order_create_before',
            [
                'quote' => $quote,
                'order_data' => $orderData,
            ]
        );
        Mage::app()->setCurrentStore($quote->getStoreId());
        $quote->collectTotals();
        Bold_Checkout_Api_Platform_Orders_TaxProcessor::addTaxesToQuote($quote, $orderData);
        $quote->save();
        $order = Bold_Checkout_Service_CreateOrderFromQuote::create($quote, $orderData);
        self::saveCustomerAddresses($order, $orderData);
        self::processTransactions($order);
        if (round($quote->getBaseGrandTotal(), 2) - round($orderData->total, 2)) {
            self::addCommentToOrder($order, $orderData);
        }
        Mage::dispatchEvent(
            'bold_checkout_order_create_after',
            [
                'order_data' => $orderData,
                'order' => $order,
            ]
        );
        return current(Bold_Checkout_Service_Extractor_Order::extract([$order]));
    }
}
