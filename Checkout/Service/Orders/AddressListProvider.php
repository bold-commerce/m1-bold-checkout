<?php

/**
 * Update provided address list by new ones created from provided data.
 */
class Bold_Checkout_Service_Orders_AddressListProvider
{
    const COMPARABLE_FIELDS = [
        'city',
        'company',
        'country_id',
        'firstname',
        'lastname',
        'telephone',
        'postcode',
        'region',
        'street',
    ];

    /**
     * Update provided address list by new ones created from provided data.
     *
     * @param Mage_Customer_Model_Address[] $existingAddresses
     * @param stdClass $billingAddressData
     * @param stdClass[] $shippingAddressesData
     * @return Mage_Customer_Model_Address[]
     * @throws \Exception
     */
    public static function getActualAddresses(
        array $existingAddresses,
        stdClass $billingAddressData,
        array $shippingAddressesData
    ) {
        $billingAddress = Bold_Checkout_Service_Hydrator_CustomerAddress::hydrate($billingAddressData);
        $identicalAddresses = self::getIdenticalAddresses($billingAddress, $existingAddresses);
        if (empty($identicalAddresses)) {
            $billingAddress->setIsDefaultBilling(true);
            $existingAddresses[] = $billingAddress;
        } else {
            reset($identicalAddresses)->setIsDefaultBilling(true);
        }
        foreach ($shippingAddressesData as $shippingAddressData) {
            $shippingAddress = Bold_Checkout_Service_Hydrator_CustomerAddress::hydrate($shippingAddressData);
            $identicalAddresses = self::getIdenticalAddresses($shippingAddress, $existingAddresses);
            if (empty($identicalAddresses)) {
                $shippingAddress->setIsDefaultShipping(true);
                $existingAddresses[] = $shippingAddress;
            } else {
                reset($identicalAddresses)->setIsDefaultShipping(true);
            }
        }

        return $existingAddresses;
    }

    /**
     * Find addresses identical to the provided.
     *
     * @param Mage_Customer_Model_Address $needle
     * @param Mage_Customer_Model_Address[] $haystack
     * @return Mage_Customer_Model_Address[]
     */
    private static function getIdenticalAddresses(Mage_Customer_Model_Address $needle, array $haystack)
    {
        return array_filter(
            $haystack,
            function (Mage_Customer_Model_Address $haystackElement) use ($needle) {
                $differenceInFields =
                    array_keys(
                        array_merge(
                            array_diff_assoc($needle->getData(), $haystackElement->getData()),
                            array_diff_assoc($haystackElement->getData(), $needle->getData())
                        )
                    );

                return empty(array_intersect($differenceInFields, self::COMPARABLE_FIELDS));
            }
        );
    }
}
