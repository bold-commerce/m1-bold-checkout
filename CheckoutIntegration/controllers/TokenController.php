<?php

/**
 * Exchange tokens controller.
 */
class Bold_CheckoutIntegration_TokenController extends Mage_Core_Controller_Front_Action
{
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_INTERNAL_ERROR = 500;

    /**
     * Get request token action.
     */
    public function requestAction()
    {
        try {
            $requestUrl = Bold_CheckoutIntegration_Model_RequestService::getRequestUrl($this->getRequest());
            $request = Bold_CheckoutIntegration_Model_RequestService::prepareRequest($this->getRequest());
            $response = Bold_CheckoutIntegration_Model_OauthService::getRequestToken(
                $request,
                $requestUrl,
                $this->getRequest()->getMethod()
            );
        } catch (\Exception $exception) {
            $response = $this->prepareErrorResponse($exception, $this->getResponse());
        }
        $this->getResponse()->setBody(http_build_query($response));
    }

    /**
     * Get access token action.
     */
    public function accessAction()
    {
        try {
            $requestUrl = Bold_CheckoutIntegration_Model_RequestService::getRequestUrl($this->getRequest());
            $request = Bold_CheckoutIntegration_Model_RequestService::prepareRequest($this->getRequest());
            $response = Bold_CheckoutIntegration_Model_OauthService::generateAccessToken(
                $request,
                $requestUrl,
                $this->getRequest()->getMethod()
            );
            $consumer = Bold_CheckoutIntegration_Model_OauthService::loadConsumerByKey($request['oauth_consumer_key']);
            $integration = Bold_CheckoutIntegration_Model_IntegrationService::findByConsumerId($consumer->getId());
            $integration->setStatus(Bold_CheckoutIntegration_Model_Integration::STATUS_ACTIVE);
            $integration->save();
        } catch (\Exception $exception) {
            $response = $this->prepareErrorResponse($exception, $this->getResponse());
        }
        $this->getResponse()->setBody(http_build_query($response));
    }

    /**
     * Prepare error response.
     *
     * @param Exception $exception
     * @param Zend_Controller_Response_Abstract $response
     * @return array
     */
    private function prepareErrorResponse(Exception $exception, Zend_Controller_Response_Abstract $response)
    {
        $errorMsg = $exception->getMessage();
        if ($exception instanceof Mage_Oauth_Exception) {
            $responseCode = self::HTTP_UNAUTHORIZED;
        } else {
            $errorMsg = 'internal_error&message=' . ($errorMsg ?: 'empty_message');
            $responseCode = self::HTTP_INTERNAL_ERROR;
        }
        $response->setHttpResponseCode($responseCode);
        return ['oauth_problem' => $errorMsg];
    }
}
