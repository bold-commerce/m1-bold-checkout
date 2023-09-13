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
     * @var array
     */
    private $routes = [];

    /**
     * @param Bold_Checkout_Mage|null $mage
     */
    public function __construct(Bold_Checkout_Mage $mage = null)
    {
        $this->mage = $mage ?: new Bold_Checkout_Mage();
        $this->routes['rest'] = $this->mage->getConfig()->getNode('rest')
            ? $this->mage->getConfig()->getNode('rest')->asArray()
            : [];
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
            $segmentAsRegEx = preg_replace('/\_([a-zA-z0-9\-_]*)/', '(?<$1>[a-zA-Z0-9\-_]*)', $segment);
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
                $processedRequest = Bold_CheckoutIntegration_Model_RequestService::prepareRequest($request);
                try {
                    $consumerId = Bold_CheckoutIntegration_Model_OauthService::validateAccessTokenRequest(
                        $processedRequest,
                        Bold_CheckoutIntegration_Model_RequestService::getRequestUrl($request),
                        $request->getMethod()
                    );
                } catch (Mage_Core_Exception $e) {
                    $consumerId = null;
                }
                if (!$consumerId) {
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
}
