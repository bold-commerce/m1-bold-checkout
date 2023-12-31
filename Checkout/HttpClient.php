<?php

/**
 * Perform curl request service.
 */
class Bold_Checkout_HttpClient
{
    /**
     * Perform request.
     *
     * @param string $method
     * @param string $url
     * @param int $websiteId
     * @param string|null $data
     * @param array $headers
     * @return string
     */
    public static function call($method, $url, $websiteId, $data = null, array $headers = [])
    {
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $tracingId = sha1(microtime());
        if ($boldConfig->isLogEnabled($websiteId)) {
            Mage::log(
                $tracingId . ': Outgoing Call: ' . $method . ' ' . $url,
                Zend_Log::DEBUG,
                'bold_checkout.log',
                true
            );
            Mage::log(
                $tracingId . ': Outgoing Call Data: ' . $data,
                Zend_Log::DEBUG,
                'bold_checkout.log',
                true
            );
        }
        $curl = curl_init();
        $url = self::prepareRequest($method, $curl, $url, $data);
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if ($boldConfig->isLogEnabled($websiteId)) {
            Mage::log(
                $tracingId . ': Outgoing call code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE),
                Zend_Log::DEBUG,
                'bold_checkout.log',
                true
            );
            Mage::log(
                $tracingId . ': Outgoing call result: ' . $result,
                Zend_Log::DEBUG,
                'bold_checkout.log', true
            );
        }
        curl_close($curl);
        try {
            $isJson = json_decode($result);
        } catch (Exception $e) {
            $isJson = false;
        }
        if ($isJson === false) {
            $result = json_encode(
                [
                    'errors' => [
                        'message' => 'Invalid response from Bold',
                        'code' => '500',
                    ],
                ]
            );
        }
        return $result;
    }

    /**
     * Build request with given data.
     *
     * @param string $method
     * @param resource $curl
     * @param string $url
     * @param string|null $data
     * @return string
     */
    private static function prepareRequest($method, $curl, $url, $data = null)
    {
        switch ($method) {
            case "POST":
                self::preparePostRequest($curl, $data);
                break;
            case "PUT":
                self::preparePutRequest($curl, $data);
                break;
            case 'PATCH':
                self::preparePatchRequest($curl, $data);
                break;
            case 'DELETE' :
                self::prepareDeleteRequest($curl, $data);
                break;
            default:
                if ($data) {
                    $data = json_decode($data);
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        return $url;
    }

    /**
     * Prepare post request.
     *
     * @param resource $curl
     * @param string|null $data
     * @return void
     */
    private static function preparePostRequest($curl, $data)
    {
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * Prepare put request.
     *
     * @param resource $curl
     * @param string|null $data
     * @return void
     */
    private static function preparePutRequest($curl, $data)
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * Prepare patch request.
     *
     * @param resource $curl
     * @param string|null $data
     * @return void
     */
    private static function preparePatchRequest($curl, $data)
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
    }

    /**
     * Prepare delete request.
     *
     * @param resource $curl
     * @param string|null $data
     * @return void
     */
    private static function prepareDeleteRequest($curl, $data)
    {
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
}
