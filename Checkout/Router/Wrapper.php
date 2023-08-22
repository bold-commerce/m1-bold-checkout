<?php

class Bold_Checkout_Router_Wrapper extends Mage_Core_Controller_Varien_Router_Abstract
{
    /**
     * @var Bold_Checkout_Router
     */
    private $router;

    public function __construct()
    {
        $this->router = new Bold_Checkout_Router();
    }

    public function setFront($front)
    {
        $this->router->setFront($front);
    }

    public function getFront()
    {
        return $this->router->getFront();
    }

    public function getFrontNameByRoute($routeName)
    {
        return $this->router->getFrontNameByRoute();
    }

    public function getRouteByFrontName($frontName)
    {
        return $this->router->getRouteByFrontName();
    }

    public function match(Zend_Controller_Request_Http $request)
    {
        return $this->router->match($request);
    }
}
