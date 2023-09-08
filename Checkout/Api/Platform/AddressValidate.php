<?php

/**
 * Quote address validation override.
 */
class Bold_Checkout_Api_Platform_AddressValidate
{
    /**
     * @var string[]
     */
    private static $requiredFields = [
        'first_name',
        'last_name',
        'address',
        'city',
        'country',
        'postal_code',
        'country_code',
    ];

    /**
     * Validate quote address.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function validate(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $requestBody = json_decode($request->getRawBody());
        foreach (self::$requiredFields as $requiredField) {
            if (!$requestBody->{$requiredField}) {
                return Bold_Checkout_Rest::buildResponse(
                    $response,
                    json_encode(
                        [
                            'valid' => false,
                            'errors' => [
                                'error_message' => 'Missing required field: ' . $requiredField,
                            ],
                        ]
                    )
                );
            }
        }
        try {
            Mage::dispatchEvent('bold_checkout_address_validation', ['address' => $requestBody]);
        } catch (Exception $e) {
            return Bold_Checkout_Rest::buildResponse(
                $response,
                json_encode(
                    [
                        'valid' => false,
                        'errors' => [
                            'error_message' => $e->getMessage(),
                        ],
                    ]
                )
            );
        }
        return Bold_Checkout_Rest::buildResponse(
            $response,
            json_encode(
                [
                    'valid' => true,
                ]
            )
        );
    }
}
