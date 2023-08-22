<?php

/**
 * Unit tests for Bold_Checkout_Router class.
 */
class Bold_Checkout_RouterTest extends PHPUnit_Framework_TestCase
{
    const RESPONSE_SUCCESS_GET = 'success get';
    const RESPONSE_SUCCESS_POST = 'success post';
    const RESPONSE_SUCCESS_NESTED = 'success nested';
    const RESPONSE_SUCCESS_PARAMS = 'success params %s %s';
    const RESPONSE_UNAUTHORIZED = ['errors' => ['Unauthorized.']];

    const ROUTES = [
        'basic/route' => [
            'GET' => 'Bold_Checkout_RouterTest::matchedNestedRoute',
        ],
        'basic' => [
            'GET' => 'Bold_Checkout_RouterTest::matchedRoute',
            'POST' => 'Bold_Checkout_RouterTest::matchedRoutePost',
            ':param1' => [
                'route' => [
                    ':param2' => [
                        'GET' => 'Bold_Checkout_RouterTest::matchedParams',
                    ],
                ],
            ],
        ],
    ];

    public static function matchedRoute($response)
    {
        $response->setBody(self::RESPONSE_SUCCESS_GET);
    }

    public static function matchedRoutePost($response)
    {
        $response->setBody(self::RESPONSE_SUCCESS_POST);
    }

    public static function matchedNestedRoute($response)
    {
        $response->setBody(self::RESPONSE_SUCCESS_NESTED);
    }

    public static function matchedParams($response, $param1, $param2)
    {
        $response->setBody(
            sprintf(
                self::RESPONSE_SUCCESS_PARAMS,
                $param1,
                $param2
            )
        );
    }

    /**
     * Unit tests for Bold_Checkout_Router::match method.
     *
     * @dataProvider matchDataProvider
     */
    public function testMatch($requestUri, $requestMethod, $expectedResponseBody, $expectedResponseCode, $authorized)
    {
        $requestDispatched = (int)($expectedResponseCode !== 500);
        $requestSuccessful = (int)($requestDispatched && $authorized);

        $secret = 'some big secret';
        $timestamp = 'some important date';
        $xhmac = $authorized
            ? base64_encode(hash_hmac('sha256', $timestamp, $secret, true))
            : 'incorrect X-HMAC';
        $request = $this->getMockBuilder('Zend_Controller_Request_Http')
            ->setMethods(
                ['getPathInfo', 'getMethod', 'getHeader', 'setDispatched', 'getHttpHost', 'getRequestUri', 'getRawBody']
            )
            ->getMock();
        $request->expects($this->once())->method('getPathInfo')->willReturn($requestUri);
        $request->expects($this->exactly(2))->method('getMethod')->willReturn($requestMethod);
        $request->expects($this->exactly($requestDispatched))->method('setDispatched');
        $request->expects($this->exactly($requestDispatched ? 3 : 0))
            ->method('getHeader')
            ->withConsecutive(['X-HMAC'], ['X-HMAC-Timestamp'], ['X-HMAC'])
            ->willReturnOnConsecutiveCalls($xhmac, $timestamp, $xhmac);
        $response = $this->getMockBuilder('Zend_Controller_Response_Http')
            ->setMethods(['setBody', 'setHttpResponseCode', 'getHttpResponseCode', 'getBody'])
            ->getMock();
        $response->expects($this->exactly((int)!$authorized))->method('setHttpResponseCode');
        $response->expects($this->exactly($requestDispatched))->method('setBody')
            ->with($expectedResponseBody);
        $response->expects($this->exactly($requestSuccessful))->method('getBody')
            ->willReturn('success');
        $response->expects($this->exactly($requestSuccessful))->method('getHttpResponseCode')
            ->willReturn($expectedResponseCode);

        $front = $this->getMockBuilder('Mage_Core_Controller_Varien_Front')
            ->setMethods(['getResponse'])
            ->getMock();
        $front->expects($this->once())->method('getResponse')->willReturn($response);

        $config = $this->getMockBuilder('Bold_Checkout_Model_Config')
            ->setMethods(['getSharedSecret'])
            ->getMock();
        $config->expects($this->exactly($requestDispatched))->method('getSharedSecret')->willReturn($secret);

        $mage = $this->getMockBuilder(Bold_Checkout_Mage::class)
            ->setMethods(['getSingleton', 'getIsDeveloperMode', 'log', 'getApp'])
            ->getMock();
        $mage->expects($this->any())->method('getIsDeveloperMode')->willReturn(false);
        $appMock = $this->getMockBuilder(Mage_Core_Model_App::class)
            ->setMethods(['getWebsite'])
            ->getMock();
        $websiteMock = $this->getMockBuilder(Mage_Core_Model_Website::class)
            ->setMethods(['getId'])
            ->getMock();
        $websiteMock->expects($this->any())->method('getId')->willReturn(1);
        $appMock->expects($this->any())->method('getWebsite')->willReturn($websiteMock);
        $mage->expects($this->any())->method('getApp')->willReturn($appMock);
        $mage->expects($this->any())->method('getSingleton')
            ->with(Bold_Checkout_Model_Config::RESOURCE)->willReturn($config);

        $router = new Bold_Checkout_Router($mage, self::ROUTES);
        $router->setFront($front);
        $router->match($request);
    }

    /**
     * Test route is not matched.
     *
     * @return void
     */
    public function testNotMatch()
    {
        $request = $this->getMockBuilder('Zend_Controller_Request_Http')
            ->setMethods(['getPathInfo', 'getMethod', 'getHttpHost', 'getRequestUri', 'setDispatched', 'getRawBody'])
            ->getMock();
        $request->expects($this->once())->method('getPathInfo')->willReturn('non_existing_path');
        $request->expects($this->never())->method('getHttpHost');
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->never())->method('setDispatched');
        $mage = $this->getMockBuilder('Bold_Checkout_Mage')
            ->setMethods(['log'])
            ->getMock();
        $mage->expects($this->never())->method('log');
        $router = new Bold_Checkout_Router($mage, self::ROUTES);
        $front = $this->getMockBuilder(Mage_Core_Controller_Varien_Front::class)
            ->setMethods(['getResponse'])
            ->getMock();
        $front->expects($this->never())->method('getResponse');
        $router->setFront($front);
        self::assertEquals(false, $router->match($request));
    }

    /**
     * Data provider for testMatch.
     *
     * @return array[]
     */
    public function matchDataProvider()
    {
        return [
            'Router finds basic route' => [
                '/basic',
                'GET',
                self::RESPONSE_SUCCESS_GET,
                200,
                true,
            ],
            'Router finds nested route' => [
                '/basic/route',
                'GET',
                self::RESPONSE_SUCCESS_NESTED,
                200,
                true,
            ],
            'Router finds another method' => [
                '/basic',
                'POST',
                self::RESPONSE_SUCCESS_POST,
                200,
                true,
            ],
            'Router handles uri params' => [
                '/basic/abc/route/123',
                'GET',
                sprintf(self::RESPONSE_SUCCESS_PARAMS, 'abc', 123),
                200,
                true,
            ],
            'Router skips unauthorized request' => [
                '/basic',
                'GET',
                json_encode(self::RESPONSE_UNAUTHORIZED),
                401,
                false,
            ],
        ];
    }
} 
