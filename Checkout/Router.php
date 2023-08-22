<?php

/**
 * Module router.
 */
class Bold_Checkout_Router
{
    /**
     * @var Bold_Checkout_Mage
     */
    private $mage;

    /**
     * @var Mage_Core_Controller_Varien_Front
     */
    private $front;

    /**
     * @var string[]
     */
    private $hmacVerificationExemptions = [
        'Bold_Checkout_Api_Platform_Destination::verify',
    ];

    /**
     * @var array
     */
    private $routes = [
        'bold' => [
            'v1' => [
                'shops' => [
                    ':shopIdentifier' => [
                        'verification' => [
                            'GET' => 'Bold_Checkout_Api_Platform_Destination::verify',
                        ],
                        'categories' => [
                            'GET' => 'Bold_Checkout_Api_Platform_Categories::getList',
                        ],
                        'products' => [
                            'GET' => 'Bold_Checkout_Api_Platform_Products::getList',
                        ],
                        'customers' => [
                            'GET' => 'Bold_Checkout_Api_Platform_Customers::getList',
                            'POST' => 'Bold_Checkout_Api_Platform_Customers::create',
                            ':customerId' => [
                                'DELETE' => 'Bold_Checkout_Api_Platform_Customers::remove',
                                'PATCH' => 'Bold_Checkout_Api_Platform_Customers::update',
                                'validate' => [
                                    'POST' => 'Bold_Checkout_Api_Platform_Customers::validate',
                                ],
                                'addresses' => [
                                    'POST' => 'Bold_Checkout_Api_Platform_CustomerAddresses::create',
                                    ':addressId' => [
                                        'DELETE' => 'Bold_Checkout_Api_Platform_CustomerAddresses::remove',
                                        'PATCH' => 'Bold_Checkout_Api_Platform_CustomerAddresses::update',
                                    ],
                                ],
                            ],
                        ],
                        'orders' => [
                            'GET' => 'Bold_Checkout_Api_Platform_Orders::getList',
                            'POST' => 'Bold_Checkout_Api_Platform_Orders::create',
                            ':orderId' => [
                                'GET' => 'Bold_Checkout_Api_Platform_Orders::get',
                                'PATCH' => 'Bold_Checkout_Api_Platform_Orders::update',
                                'payments' => [
                                    ':paymentId' => [
                                        'PATCH' => 'Bold_Checkout_Api_Platform_OrderPayments::update',
                                    ],
                                ],
                            ],
                        ],
                        'overrides' => [
                            'inventory' => [
                                'POST' => 'Bold_Checkout_Api_Platform_Inventory::check',
                            ],
                            'shipping' => [
                                'POST' => 'Bold_Checkout_Api_Platform_Shipping::calculate',
                            ],
                            'tax' => [
                                'POST' => 'Bold_Checkout_Api_Platform_Taxes::calculate',
                            ],
                            'discount' => [
                                'POST' => 'Bold_Checkout_Api_Platform_Discount::apply',
                            ],
                            'address_validate' => [
                                'POST' => 'Bold_Checkout_Api_Platform_AddressValidate::validate',
                            ]
                        ],
                        'webhooks' => [
                            'order' => [
                                'created' => [
                                    'POST' => 'Bold_Checkout_Api_Platform_OrdersWebhooks::created',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @param Bold_Checkout_Mage|null $mage
     * @param array $routes
     */
    public function __construct(Bold_Checkout_Mage $mage = null, array $routes = [])
    {
        $this->mage = $mage ?: new Bold_Checkout_Mage();
        $this->routes = $routes ?: $this->routes;
    }

    /**
     * @param Mage_Core_Controller_Varien_Front $front
     * @return void
     */
    public function setFront(Mage_Core_Controller_Varien_Front $front)
    {
        $this->front = $front;
    }

    /**
     * @return Mage_Core_Controller_Varien_Front
     */
    public function getFront()
    {
        return $this->front;
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $routes
     * @param string $currPath
     * @return array|null
     */
    private function matchSegment($path, $method, array $routes, $currPath = '')
    {
        krsort($routes);
        foreach ($routes as $segment => $subRoutes) {
            $segmentAsRegEx = preg_replace('/\:([a-zA-z0-9\-_]*)/', '(?<$1>[a-zA-Z0-9\-_]*)', $segment);
            $prefix = $currPath . '/' . $segmentAsRegEx;
            if (preg_match('!^' . $prefix . '$!', $path, $matches)) {
                if (isset($subRoutes[$method])) {
                    unset($matches[0]);
                    return [$subRoutes[$method], $matches];
                } else {
                    return null;
                }
            } elseif (preg_match('!^' . $prefix . '!', $path)) {
                return $this->matchSegment($path, $method, $subRoutes, $prefix);
            }
        }
        return null;
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $function
     * @param array $matchedArguments
     * @return array
     */
    private function buildCallArguments(ReflectionMethod $function, array $matchedArguments)
    {
        $arguments = [];
        foreach ($function->getParameters() as $parameter) {
            if (isset($matchedArguments[$parameter->getName()])) {
                $arguments[$parameter->getName()] = $matchedArguments[$parameter->getName()];
            }
        }
        return $arguments;
    }

    /**
     * @param string $name
     * @param array $matchedArguments
     * @param Zend_Controller_Request_Http $request
     * @param Zend_Controller_Response_Http $response
     * @return mixed
     * @throws InvalidArgumentException
     * @throws BadFunctionCallException
     */
    private function invokeHandler(
        $name,
        array $matchedArguments,
        Zend_Controller_Request_Http $request,
        Zend_Controller_Response_Http $response
    ) {
        if (strpos($name, '::') !== -1) {
            list($className, $methodName) = explode('::', $name);
            if (!is_callable([$className, $methodName])) {
                throw new BadFunctionCallException();
            }
            $reflectionClass = new ReflectionClass($className);
            $function = $reflectionClass->getMethod($methodName);
            $matchedArguments['request'] = $request;
            $matchedArguments['response'] = $response;
            return $function->invokeArgs(null, $this->buildCallArguments($function, $matchedArguments));
        } else {
            if (!is_callable($name)) {
                throw new InvalidArgumentException();
            }
            $function = new ReflectionFunction($name);
            return $function->invokeArgs($this->buildCallArguments($function, $matchedArguments));
        }
    }

    /**
     * @param Zend_Controller_Request_Http $request
     * @return mixed|Zend_Controller_Response_Http
     * @throws Exception
     */
    public function match(Zend_Controller_Request_Http $request)
    {
        $tracingId = sha1(microtime());
        $handlerFunction = $this->matchSegment(
            $request->getPathInfo(),
            $request->getMethod(),
            $this->routes
        );
        if ($handlerFunction) {
            $websiteId = $this->mage->getApp()->getWebsite()->getId();
            $this->mage->log($tracingId . ': Matched route.', $websiteId);
            $this->logRequest($tracingId, $request, $websiteId);
            $response = $this->getFront()->getResponse();
            try {
                if (!$this->authorizeAction($request, $handlerFunction, $websiteId)) {
                    $this->mage->log($tracingId . ': Authorization failed.', $websiteId);
                    $response->setBody(json_encode(['errors' => ['Unauthorized.']]));
                    $response->setHttpResponseCode(401);
                    $request->setDispatched();
                    return $response;
                }
                list($functionName, $matchedArguments) = $handlerFunction;
                $response = $this->invokeHandler(
                    $functionName, $matchedArguments, $request, $response
                ) ?: $response;
            } catch (BadFunctionCallException $e) {
                $response->setHttpResponseCode(501);
            } catch (InvalidArgumentException $e) {
                $response->setHttpResponseCode(400);
                $response->setBody(json_encode(['error' => $e->getMessage()]));
            } catch (Exception $e) {
                if ($this->mage->getIsDeveloperMode()) {
                    throw $e;
                }
                $response->setHttpResponseCode(500);
                $response->setBody(json_encode(['error' => $e->getMessage()]));
            }
            $request->setDispatched();
            $this->mage->log($tracingId . ': ' . $response->getHttpResponseCode(), $websiteId);
            $body = $response->getBody();
            if (strlen($body) > 500) {
                $this->mage->log(
                    $tracingId . ': ' . substr($body, 0, 200) . ' ... ' . substr($body, -200, 200),
                    $websiteId
                );
            } else {
                $this->mage->log($tracingId . ': ' . $body, $websiteId);
            }
            return $response;
        }
        return false;
    }

    /**
     * Perform request authorization.
     *
     * @param Zend_Controller_Request_Http $request
     * @param array $handlerFunction
     * @param int $websiteId
     * @return bool
     * @throws Zend_Controller_Request_Exception
     */
    private function authorizeAction(Zend_Controller_Request_Http $request, array $handlerFunction, $websiteId)
    {
        $handlerFunctionName = isset($handlerFunction[0]) ? $handlerFunction[0] : '';
        if (in_array($handlerFunctionName, $this->hmacVerificationExemptions)) {
            return $request->getHeader('User-Agent') === 'Bold-API';
        }
        if ($request->getHeader('X-HMAC')) {
            return $this->verifyXHMAC($request, $websiteId);
        }
        if ($request->getHeader('Signature')) {
            return $this->verifySignature($request, $websiteId);
        }
        return $this->verifyAuthorizationHeader($request, $websiteId);
    }

    /**
     * Log Bold requests.
     *
     * @param string $tracingId
     * @param Zend_Controller_Request_Http $request
     * @param int $websiteId
     * @return void
     */
    public function logRequest($tracingId, Zend_Controller_Request_Http $request, $websiteId)
    {
        $this->mage->log('', $websiteId);
        $this->mage->log(
            $tracingId . ': ' . $request->getMethod() . ' ' . $request->getHttpHost() . $request->getRequestUri(),
            $websiteId
        );
        $this->mage->log($tracingId . ': ' . $request->getRawBody(), $websiteId);
    }

    /**
     * Get Authorization header name (if set).
     *
     * @return null|string
     */
    private function getAuthorizationHeaderName()
    {
        //phpcs:ignore MEQP1.Security.Superglobal.SuperglobalUsageWarning
        $headerNames = array_keys($_SERVER);
        $filteredHeaderNames = array_filter(
            $headerNames,
            function ($header) {
                return preg_match('/^(REDIRECT_)*HTTP_AUTHORIZATION$/', $header);
            }
        );
        natsort($filteredHeaderNames);

        return end($filteredHeaderNames);
    }

    /**
     * Verify HMAC header.
     *
     * @param Zend_Controller_Request_Http $request
     * @param int $websiteId
     * @return bool
     * @throws Zend_Controller_Request_Exception
     */
    private function verifyXHMAC(Zend_Controller_Request_Http $request, $websiteId)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = $this->mage->getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $sharedSecret = $config->getSharedSecret($websiteId);
        return hash_equals(
            base64_encode(
                hash_hmac(
                    'sha256',
                    $request->getHeader('X-HMAC-Timestamp'),
                    $sharedSecret,
                    true
                )
            ),
            $request->getHeader('X-HMAC')
        );
    }

    /**
     * Verify the request signed using the Signing HTTP Messages IETF draft standard (Version 12).
     *
     * @param Zend_Controller_Request_Http $request
     * @param int $websiteId
     * @return bool
     * @throws Zend_Controller_Request_Exception
     */
    private function verifySignature(Zend_Controller_Request_Http $request, $websiteId)
    {
        preg_match('/signature="(\S*?)"/', $request->getHeader('Signature'), $matches);
        $signature = isset($matches[1]) ? $matches[1] : null;
        if (!$signature) {
            return false;
        }
        /** @var Bold_Checkout_Model_Config $config */
        $config = $this->mage->getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $sharedSecret = $config->getSharedSecret($websiteId);
        $headersToSign = sprintf(
            '(request-target): %s %s%sdate: %s',
            strtolower($request->getMethod()),
            $request->getRequestUri(),
            PHP_EOL,
            $request->getHeader('Date')
        );
        $hash = hash_hmac('sha256', $headersToSign, $sharedSecret, true);
        $hashEncoded = base64_encode($hash);
        return hash_equals($hashEncoded, $signature);
    }

    /**
     * Verify authorization header with fall back to redirect auth.
     *
     * @param Zend_Controller_Request_Http $request
     * @param int $websiteId
     * @return bool
     * @throws Zend_Controller_Request_Exception
     */
    private function verifyAuthorizationHeader(Zend_Controller_Request_Http $request, $websiteId)
    {
        preg_match('/signature="(\S*)"/', $request->getHeader('X-Bold-Api-Authorization'), $matches);
        $signature = isset($matches[1]) ? $matches[1] : null;
        if (!$signature) {
            $authorizationHeaderName = $this->getAuthorizationHeaderName();
            if (!$authorizationHeaderName) {
                return false;
            }
            // phpcs:disable MEQP1.Security.Superglobal.SuperglobalUsageWarning
            preg_match('/signature="(\S*)"/', $_SERVER[$authorizationHeaderName], $matches);
            // phpcs:enable MEQP1.Security.Superglobal.SuperglobalUsageWarning
            $signature = isset($matches[1]) ? $matches[1] : null;
            if (!$signature) {
                return false;
            }
        }
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = $this->mage->getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        return hash_equals(
            base64_encode(
                hash_hmac(
                    'sha256',
                    'x-bold-date: ' . $request->getHeader('x-bold-date'),
                    $boldConfig->getSharedSecret($websiteId),
                    true
                )
            ),
            $signature
        );
    }
}
