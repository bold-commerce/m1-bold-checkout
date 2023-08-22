<?php

/**
 * Is product simple or complex verification service.
 */
class Bold_Checkout_Service_ProductHasVariants
{
    /**
     * @var array
     */
    private static $complexProductTypes = [
        Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
        Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
        Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
    ];

    /**
     * Check if product has variations.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public static function verify(Mage_Catalog_Model_Product $product)
    {
        return in_array($product->getTypeId(), self::$complexProductTypes);
    }
}
