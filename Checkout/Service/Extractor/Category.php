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
        $customAttributes = [];
        foreach ($category->getAttributes() as $attribute) {
            if ($attribute->getBackendType() === Mage_Eav_Model_Entity_Attribute_Abstract::TYPE_STATIC) {
                continue;
            }
            $customAttributes[] = [
                'attribute_code' => $attribute->getAttributeCode(),
                'value' => $category->getData($attribute->getAttributeCode()),
            ];
        }
        $createdAt = strtotime($category->getCreatedAt()) > 0 ? $category->getCreatedAt() : now();
        $updatedAt = strtotime($category->getUpdatedAt()) > 0 ? $category->getUpdatedAt() : now();
        return [
            'id' => (int)$category->getId(),
            'parent_id' => (int)$category->getParentId(),
            'name' => $category->getName(),
            'position' => (int)$category->getPosition(),
            'level' => (int)$category->getLevel(),
            'children' => (string)$category->getChildren(),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'path' => (string)$category->getPath(),
            'include_in_menu' => (bool)$category->getIncludeInMenu(),
            'custom_attributes' => $customAttributes
        ];
    }
}
