<?php

/**
 * Customer entity to array extract service.
 */
class Bold_Checkout_Service_Extractor_AttributeSet
{
    /**
     * Extract customers data.
     *
     * @param array $attributeSets
     * @return Mage_Eav_Model_Entity_Attribute_Set[]
     */
    public static function extract(array $attributeSets)
    {
        $result = [];
        foreach ($attributeSets as $attributeSet) {
            $result[] = self::extractAttributeSet($attributeSet);
        }

        return $result;
    }

    /**
     * Extract customer entity data into array.
     *
     * @param Mage_Eav_Model_Entity_Attribute_Set $attributeSet
     * @return array
     */
    private static function extractAttributeSet(Mage_Eav_Model_Entity_Attribute_Set $attributeSet)
    {
        return [
            'attribute_set_id' => (int)$attributeSet->getId(),
            'attribute_set_name' => $attributeSet->getAttributeSetName(),
            'sort_order' => (int)$attributeSet->getSortOrder(),
            'entity_type_id' => (int)$attributeSet->getEntityTypeId(),
        ];
    }
}
