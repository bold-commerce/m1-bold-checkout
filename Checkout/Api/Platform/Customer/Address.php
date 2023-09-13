<?php

/**
 * Customer address validation rest service.
 */
class Bold_Checkout_Api_Platform_Customer_Address
{
    /**
     * Validate customer address endpoint.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function validate(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $payload = json_decode($request->getRawBody());
        $address = self::getAddress($payload);
        $validationResult = $address->validate();
        if ($validationResult === true) {
            return Bold_Checkout_Rest::buildResponse($response, json_encode(
                [
                    'valid' => true,
                    'errors' => [],
                ]
            ));
        }
        $errors = [];
        foreach ($validationResult as $error) {
            $errors[] = [
                'code' => 422,
                'type' => 'server.validation_error',
                'message' => $error,
            ];
        }
        $result = [
            'valid' => false,
            'errors' => $errors,
        ];
        return Bold_Checkout_Rest::buildResponse($response, json_encode($result));
    }

    /**
     * Get address from payload.
     *
     * @param stdClass $payload
     * @return Mage_Customer_Model_Address
     */
    private static function getAddress(stdClass $payload)
    {
        /** @var Mage_Customer_Model_Address $address */
        $address = Mage::getModel('customer/address');
        $regionId = isset($payload->address->region->region_code)
            ? $payload->address->region->region_code
            : null;
        $countryId = isset($payload->address->country_id)
            ? $payload->address->country_id
            : null;
        $street1 = isset($payload->address->street[0])
            ? $payload->address->street[0]
            : null;
        $street2 = isset($payload->address->street[1])
            ? $payload->address->street[1]
            : null;
        $postcode = isset($payload->address->postcode)
            ? $payload->address->postcode
            : null;
        $telephone = isset($payload->address->telephone)
            ? $payload->address->telephone
            : null;
        $city = isset($payload->address->city)
            ? $payload->address->city
            : null;
        $firstname = isset($payload->address->firstname)
            ? $payload->address->firstname
            : null;
        $lastname = isset($payload->address->lastname)
            ? $payload->address->lastname
            : null;
        $address->setRegionId($regionId);
        $address->setCountryId($countryId);
        $address->setStreet([$street1, $street2]);
        $address->setPostcode($postcode);
        $address->setTelephone($telephone);
        $address->setCity($city);
        $address->setFirstname($firstname);
        $address->setLastname($lastname);
        return $address;
    }
}
