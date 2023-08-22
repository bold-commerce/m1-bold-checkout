<?php

/**
 * Customer entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Customer
{
    /**
     * Extract customers data.
     *
     * @param array $customers
     * @return Mage_Customer_Model_Customer[]
     */
    public static function extract(array $customers)
    {
        $result = [];
        foreach ($customers as $customer) {
            $result[] = self::extractCustomer($customer);
        }

        return $result;
    }

    /**
     * Extract customer entity data into array.
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return array
     */
    private static function extractCustomer(Mage_Customer_Model_Customer $customer)
    {
        $addresses = [];
        foreach ($customer->getAddresses() as $customerAddress) {
            if ($customerAddress->getCustomer()) {
                $addresses[] = Bold_Checkout_Service_Extractor_Customer_Address::extract($customerAddress);
            }
        }
        $updatedAt = strtotime($customer->getUpdatedAt()) > 0 ? $customer->getUpdatedAt() : now();
        $createdAt = strtotime($customer->getCreatedAt()) > 0 ? $customer->getCreatedAt() : now();
        return [
            'platform_id' => (string)$customer->getId(),
            'platform_updated_at' => Mage::getSingleton('core/date')->date('c', strtotime($updatedAt)),
            'platform_created_at' => Mage::getSingleton('core/date')->date('c', strtotime($createdAt)),
            'email' => (string)$customer->getEmail(),
            'first_name' => (string)$customer->getFirstname(),
            'last_name' => (string)$customer->getLastname(),
            'phone' => $customer->getDefaultBillingAddress()
                ? (string)$customer->getDefaultBillingAddress()->getTelephone()
                : '',
            'addresses' => $addresses,
        ];
    }
}
