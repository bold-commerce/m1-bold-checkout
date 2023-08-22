<?php

/**
 * Platform customer service.
 */
class Bold_Checkout_Api_Platform_Customers
{
    /**
     * Retrieve magento customers.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function getList(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $listBuilder = function ($limit, $cursor, $websiteId, $params = []) {
            $list = Bold_Checkout_Model_Resource_CustomerListBuilder::build($limit, $cursor, $websiteId, $params);
            $date = Mage::getModel('core/date')->gmtDate();
            Bold_Checkout_Model_Resource_SaveEntitySynchronizationTime::save(
                $list->getColumnValues('entity_id'),
                Bold_Checkout_Service_Synchronizer::ENTITY_TYPE_CUSTOMER,
                $websiteId,
                $date
            );
            return Bold_Checkout_Service_Extractor_Customer::extract($list->getItems());
        };
        try {
            return Bold_Checkout_Rest::buildListResponse($request, $response, 'customers', $listBuilder);
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
    }

    /**
     * Create customer on magento side.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function create(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $selfHosted = $config->isCheckoutTypeSelfHosted($websiteId);
        if ($selfHosted) {
            return Bold_Checkout_Rest::buildResponse(
                $response,
                null,
                201
            );
        }
        $requestBody = json_decode($request->getRawBody());
        if (!isset($requestBody->data->customer)) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify customer data in request.',
                400,
                'server.validation_error'
            );
        }
        $customerData = $requestBody->data->customer;
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
        $customer->loadByEmail($customerData->email);
        if ($customer->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Customer with email: "%s" already exists.', $customerData->email),
                409,
                'server.validation_error'
            );
        }
        try {
            $customer = Bold_Checkout_Service_Hydrator_Customer::hydrate($customerData);
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
            $customer->setPassword($customer->generatePassword());
            $customer->setPasswordCreatedAt(Mage::getSingleton('core/date')->gmtTimestamp());
            $customer->setForceConfirmed(true);
            $customer->save();
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        $body = current(Bold_Checkout_Service_Extractor_Customer::extract([$customer]));

        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode($body),
            201
        );
    }

    /**
     * De-activate customer on magento side.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @param int $customerId
     * @return Mage_Core_Controller_Response_Http
     */
    public static function remove(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response,
        $customerId
    ) {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        $customer->load((int)$customerId);
        if (!$customer->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Customer with platform id: "%s" does not exist.', $customerId),
                409,
                'server.validation_error'
            );
        }
        $customer->setData('is_active', '0');
        try {
            $customer->save();
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }

        return Bold_Checkout_Rest::buildResponse($response, null, 204);
    }

    /**
     * Update customer data in magento.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @param int $customerId
     * @return Mage_Core_Controller_Response_Http
     */
    public static function update(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response,
        $customerId
    ) {
        $requestBody = json_decode($request->getRawBody());
        $customer = Mage::getModel('customer/customer');
        $customer->load((int)$customerId);
        if (!$customer->getId()) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                sprintf('Customer with platform id: "%s" does not exist.', $customerId),
                409,
                'server.validation_error'
            );
        }
        if (!isset($requestBody->data->customer)) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                'Please specify customer data in request.',
                400,
                'server.validation_error'
            );
        }
        try {
            $customer = Bold_Checkout_Service_Hydrator_Customer::hydrate($requestBody->data->customer);
            $customer->save();
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        $body = current(Bold_Checkout_Service_Extractor_Customer::extract([$customer]));

        return Bold_Checkout_Rest::buildResponse($response, json_encode($body));
    }

    /**
     * Get customer data.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function get(Mage_Core_Controller_Request_Http $request, Mage_Core_Controller_Response_Http $response)
    {
        $perPage = (int)$request->getParam('per_page', 100);
        $page = (int)$request->getParam('page', 1);
        $email = $request->getParam('email', null);
        /** @var Mage_Customer_Model_Resource_Customer_Collection $customers */
        try {
            $customers = Mage::getModel('customer/customer')->getCollection();
            $customers->addAttributeToSelect('firstname');
            $customers->addAttributeToSelect('lastname');
            $customers->addAttributeToSelect('default_billing');
            $customers->addAttributeToSelect('default_shipping');
        } catch (Mage_Core_Exception $e) {
            return Bold_Checkout_Rest::buildErrorResponse($response, $e->getMessage());
        }
        if ($email !== null) {
            $customers->addFieldToFilter('email', $email);
        }
        $customers->setPageSize($perPage);
        $customers->setCurPage($page);
        $result = [];
        /** @var Mage_Customer_Model_Customer $customer */
        foreach ($customers as $customer) {
            $customerData = [
                'id' => (int)$customer->getId(),
                'date_created' => Mage::getSingleton('core/date')->date(
                    'Y-m-d\TH:i:s',
                    strtotime($customer->getCreatedAt())
                ),
                'date_created_gmt' => Mage::getSingleton('core/date')->gmtDate(
                    'Y-m-d\TH:i:s',
                    strtotime($customer->getCreatedAt())
                ),
                'date_modified' => Mage::getSingleton('core/date')->date(
                    'Y-m-d\TH:i:s',
                    strtotime($customer->getUpdatedAt())
                ),
                'date_modified_gmt' => Mage::getSingleton('core/date')->gmtDate(
                    'Y-m-d\TH:i:s',
                    strtotime($customer->getUpdatedAt())
                ),
                'email' => $customer->getEmail(),
                'first_name' => $customer->getFirstname(),
                'last_name' => $customer->getLastname(),
                'role' => 'customer',
                'is_paying_customer' => true,
                'avatar_url' => '',
                'meta_data' => [],
            ];
            $shippingAddress = $customer->getDefaultShippingAddress();
            if ($shippingAddress) {
                $customerData['shipping'] = [
                    'address_1' => $shippingAddress->getStreet1(),
                    'address_2' => $shippingAddress->getStreet2(),
                    'city' => $shippingAddress->getCity(),
                    'state' => $shippingAddress->getRegionCode(),
                    'postcode' => $shippingAddress->getPostcode(),
                    'country' => $shippingAddress->getCountry(),
                    'company' => '',
                ];
            }
            $billingAddress = $customer->getDefaultBillingAddress();
            if ($billingAddress) {
                $customerData['billing'] = [
                    'first_name' => $billingAddress->getFirstname(),
                    'last_name' => $billingAddress->getLastname(),
                    'address_1' => $billingAddress->getStreet1(),
                    'address_2' => $billingAddress->getStreet2(),
                    'city' => $billingAddress->getCity(),
                    'state' => $billingAddress->getRegionCode(),
                    'postcode' => $billingAddress->getPostcode(),
                    'country' => $billingAddress->getCountry(),
                    'email' => $customer->getEmail(),
                    'phone' => $billingAddress->getTelephone(),
                ];
            }
            $result[] = $customerData;
        }
        return Bold_Checkout_Rest::buildResponse($response, json_encode($result));
    }
}
