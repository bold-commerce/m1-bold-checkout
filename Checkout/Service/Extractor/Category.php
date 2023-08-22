<?php

/**
 * Category entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_Category
{
    /**
     * Extract categories data.
     *
     * @param array $categories
     * @return Mage_Catalog_Model_Category[]
     */
    public static function extract(array $categories)
    {
        $result = [];
        foreach ($categories as $category) {
            $result[] = self::extractCategory($category);
        }

        return $result;
    }

    /**
     * Extract category entity data into array.
     *
     * @param Mage_Catalog_Model_Category $category
     * @return array
     */
    private static function extractCategory(Mage_Catalog_Model_Category $category)
    {
        $updatedAt = strtotime($category->getUpdatedAt()) > 0 ? $category->getUpdatedAt() : now();
        $createdAt = strtotime($category->getCreatedAt()) > 0 ? $category->getCreatedAt() : now();
        return [
            'platform_id' => (string)$category->getId(),
            'platform_updated_at' => Mage::getSingleton('core/date')->date('c', strtotime($updatedAt)),
            'platform_created_at' => Mage::getSingleton('core/date')->date('c', strtotime($createdAt)),
            'image_url' => (string)$category->getImageUrl(),
            'name' => (string)$category->getName(),
        ];
    }
}
