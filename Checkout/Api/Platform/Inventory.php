<?php

/**
 * Platform inventory service.
 */
class Bold_Checkout_Api_Platform_Inventory
{
    const INITIAL_STAGE = 'initial';
    const FINAL_STAGE = 'final';
    const AVAILABLE_STAGES = [
        self::INITIAL_STAGE,
        self::FINAL_STAGE,
    ];

    const ITEMS_FIELD = 'items';
    const TYPE_FIELD = 'type';

    const SKU_ITEM_FIELD = 'sku';
    const ID_ITEM_FIELD = 'variant_id';
    const QTY_ITEM_FIELD = 'quantity';
    const AVAILABLE_QTY_ITEM_FIELD = 'available_quantity';

    const REQUEST_REQUIRED_ITEM_FIELDS = [
        self::SKU_ITEM_FIELD,
        self::ID_ITEM_FIELD,
        self::QTY_ITEM_FIELD,
    ];

    /**
     * Get product availability|salable status.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @param Mage_Core_Controller_Response_Http $response
     * @return Mage_Core_Controller_Response_Http
     */
    public static function check(
        Mage_Core_Controller_Request_Http $request,
        Mage_Core_Controller_Response_Http $response
    ) {
        $requestBody = json_decode($request->getRawBody(), true);
        Mage::dispatchEvent('bold_checkout_inventory_check_before', ['request_body' => $requestBody]);
        $errors = self::checkIncomingData($requestBody);
        if ($errors) {
            return Bold_Checkout_Rest::buildErrorResponse(
                $response,
                implode(' ', $errors),
                400,
                'server.validation_error'
            );
        }
        $items = [];
        foreach ($requestBody[self::ITEMS_FIELD] as $item) {
            $sku = $item[self::SKU_ITEM_FIELD];
            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if (!$product) {
                return Bold_Checkout_Rest::buildErrorResponse(
                    $response,
                    sprintf('Product with sku "%s" has not been found.', $sku),
                    409,
                    'server.validation_error'
                );
            }
            $quantity = $item[self::QTY_ITEM_FIELD];
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $availableQuantity = $stockItem->checkQty($quantity) ? $quantity : $stockItem->getStockQty();
            $items[] = [
                self::SKU_ITEM_FIELD => (string)$product->getSku(),
                self::ID_ITEM_FIELD => (string)$product->getId(),
                self::QTY_ITEM_FIELD => (int)$quantity,
                self::AVAILABLE_QTY_ITEM_FIELD => (int)$availableQuantity,
            ];
        }
        $type = $requestBody[self::TYPE_FIELD];
        $result = [
            self::TYPE_FIELD => $type,
            self::ITEMS_FIELD => $items,
        ];
        Mage::dispatchEvent(
            'bold_checkout_inventory_check_after',
            [
                'request_body' => $requestBody,
                'result' => $result,
            ]
        );
        return Bold_Checkout_Rest::buildResponse($response, json_encode($result));
    }

    /**
     * Validate request data.
     *
     * @param array $requestBody
     * @return array
     */
    private static function checkIncomingData(array $requestBody)
    {
        $errors = [];
        if (!isset($requestBody[self::TYPE_FIELD])) {
            $errors[] = 'No stage of the order provided.';
        }
        if (isset($requestBody[self::TYPE_FIELD])
            && !in_array($requestBody[self::TYPE_FIELD], self::AVAILABLE_STAGES)) {
            $errors[] = 'Incorrect stage of the order provided.';
        }
        if (!isset($requestBody[self::ITEMS_FIELD])) {
            $errors[] = 'No order items provided.';
        } elseif (!is_array($requestBody[self::ITEMS_FIELD])) {
            $errors[] = 'Incorrect order items provided.';
        } else {
            foreach ($requestBody[self::ITEMS_FIELD] as $index => $item) {
                foreach (self::REQUEST_REQUIRED_ITEM_FIELDS as $requiredField) {
                    if (!isset($item[$requiredField])) {
                        $errors[] = sprintf('No \'%s\' field provided for item \'%s\'.', $requiredField, $index);
                    }
                }
            }
        }

        return $errors;
    }
}
