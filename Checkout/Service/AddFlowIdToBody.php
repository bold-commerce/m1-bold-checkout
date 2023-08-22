<?php

/**
 * Add flow Id for request and response body.
 */
class Bold_Checkout_Service_AddFlowIdToBody
{
    const FLOW_ID = 'Bold-Magento1';

    /**
     * Add flow id for Bold statistics in request|response body.
     *
     * @param string|null $data
     * @return string|null
     */
    public static function addFlowId($data = null)
    {
        if (!$data) {
            return $data;
        }
        $data = json_decode($data);
        $data->flow_id = self::FLOW_ID;
        return json_encode($data);
    }
}
