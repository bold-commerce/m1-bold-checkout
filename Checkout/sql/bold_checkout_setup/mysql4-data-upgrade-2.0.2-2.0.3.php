<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
/** @var Mage_Core_Model_Resource $resource */
$resource = Mage::getSingleton('core/resource');

$installer->startSetup();

$existingLifeElements = getLifeElements($resource);
$updatedLifeElements = updateLifeElements($allLifeElements);
saveLifeElements($resource, $updatedLifeElements);

$installer->endSetup();

/**
 * Get existing Life elements from database.
 *
 * @param Mage_Core_Model_Resource $resource
 * @return array
 */
function getLifeElements(Mage_Core_Model_Resource $resource)
{
    $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
    $table = $connection->getTableName(Mage_Core_Model_Config_Data::ENTITY);
    $select = $connection->select()->from(
        $table,
        [
            'scope',
            'scope_id',
            'path',
            'value',
        ]
    )->where(
        'path = ?',
        Bold_Checkout_Model_Config::PATH_LIFE_ELEMENTS
    );

    return $connection->fetchAll($select);
}

/**
 * Save updated Life elements to database.
 *
 * @param Mage_Core_Model_Resource $resource
 * @param array $result
 * @return void
 */
function saveLifeElements(Mage_Core_Model_Resource $resource, array $lifeElements)
{
    $connection = $resource->getConnection(\Mage_Core_Model_Resource::DEFAULT_WRITE_RESOURCE);
    $table = $connection->getTableName(Mage_Core_Model_Config_Data::ENTITY);
    $connection->insertOnDuplicate(
        $table,
        $lifeElements
    );
}

/**
 * Add to each element a 'input_regex' if it is absent.
 *
 * @param array $allLifeElements
 * @return array
 */
function updateLifeElements(array $allLifeElements)
{
    $result = [];
    foreach ($allLifeElements as $scopedLifeElement) {
        $updated = false;
        $lifeElements = unserialize($scopedLifeElement['value']);
        foreach ($lifeElements as &$lifeElement) {
            if (!isset($lifeElement['input_regex'])) {
                $lifeElement['input_regex'] = '';
                $updated = true;
            }
        }
        if ($updated) {
            $scopedLifeElement['value'] = serialize($lifeElements);
            $result[] = $scopedLifeElement;
        }
    }

    return $result;
}
