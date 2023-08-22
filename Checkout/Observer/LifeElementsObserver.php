<?php

/**
 * Observe 'admin_system_config_changed_section_checkout' event and sync (LiFE) elements.
 */
class Bold_Checkout_Observer_LifeElementsObserver
{
    const LIFE_ELEMENTS_API_URI = 'checkout/shop/{{shopId}}/life_elements';

    /**
     * Sync (LiFE) Elements.
     *
     * @param Varien_Event_Observer $observer
     * @return void
     * @throws Mage_Core_Exception
     */
    public function syncElements(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $websiteId = (int)$event->getWebsite();
        /** @var Bold_Checkout_Model_Config $boldConfig */
        $boldConfig = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        if (!$boldConfig->isCheckoutEnabled($websiteId)
            && (!$boldConfig->isCheckoutTypeStandard($websiteId)
                || !$boldConfig->isCheckoutTypeParallel($websiteId))
        ) {
            return;
        }

        $magentoLifeElements = $boldConfig->getLifeElements($websiteId);
        $boldLifeElements = $this->getLifeElementsFromBold($websiteId);
        if (!$magentoLifeElements && !$boldLifeElements) {
            return;
        }

        $this->sync($websiteId, $magentoLifeElements, $boldLifeElements);
    }

    /**
     * Synchronize (LiFE) Elements between Magento and Bold Platform.
     *
     * @param int $websiteId
     * @param array $magentoLifeElements
     * @param array $boldLifeElements
     * @return void
     * @throws Mage_Core_Exception
     */
    private function sync($websiteId, array $magentoLifeElements, array $boldLifeElements)
    {
        $magentoLifeMetaFields = array_values(
            array_map(
                function ($element) {
                    return isset($element['meta_data_field']) ? $element['meta_data_field'] : null;
                },
                $magentoLifeElements
            )
        );
        $boldLifeMetaFields = array_map(
            function ($element) {
                return isset($element['meta_data_field']) ? $element['meta_data_field'] : null;
            },
            $boldLifeElements
        );
        $metaFieldsToAdd = array_diff($magentoLifeMetaFields, $boldLifeMetaFields);
        $metaFieldsToUpdate = array_diff($magentoLifeMetaFields, $metaFieldsToAdd);
        $metaFieldsToDelete = array_diff($boldLifeMetaFields, $magentoLifeMetaFields);
        if ($metaFieldsToAdd) {
            $this->add($magentoLifeElements, $metaFieldsToAdd, $websiteId);
        }
        if ($metaFieldsToUpdate) {
            $this->update($magentoLifeElements, $metaFieldsToUpdate, $boldLifeElements, $websiteId);
        }
        if ($metaFieldsToDelete) {
            $this->delete($boldLifeElements, $metaFieldsToDelete, $websiteId);
        }
    }

    /**
     * Create (LiFE) Elements on Bold Platform.
     *
     * @param int $websiteId
     * @param array $elements
     * @return void
     * @throws Mage_Core_Exception
     */
    private function createBoldLifeElements($websiteId, array $elements)
    {
        foreach ($elements as $element) {
            $result = json_decode(
                Bold_Checkout_Service::call(
                    'POST',
                    self::LIFE_ELEMENTS_API_URI,
                    $websiteId,
                    json_encode($element),
                    true
                )
            );
            if (isset($result->errors)) {
                $error = current($result->errors);
                Mage::throwException(
                    Mage::helper('core')->__('Cannot create LiFE element on bold. %s', $error->message)
                );
            }
        }
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addNotice(
            Mage::helper('core')->__('A total of %s (LiFE) Element(s) have been added.', count($elements))
        );
    }

    /**
     * Update (LiFE) Elements on Bold Platform.
     *
     * @param int $websiteId
     * @param array $elements
     * @return void
     * @throws Mage_Core_Exception
     */
    private function updateBoldLifeElements($websiteId, array $elements)
    {
        foreach ($elements as $publicElementId => $elementData) {
            $result = json_decode(
                Bold_Checkout_Service::call(
                    'PATCH',
                    self::LIFE_ELEMENTS_API_URI . '/' . $publicElementId,
                    $websiteId,
                    json_encode($elementData),
                    true
                )
            );
            if (isset($result->errors)) {
                Mage::throwException('Cannot update LiFE element on bold.');
            }
        }
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addNotice(
            Mage::helper('core')->__('A total of %s (LiFE) Element(s) have been updated.', count($elements))
        );
    }

    /**
     * Delete (LiFE) Elements from Bold Platform.
     *
     * @param int $websiteId
     * @param array $elements
     * @return void
     * @throws Mage_Core_Exception
     */
    private function deleteBoldLifeElements($websiteId, array $elements)
    {
        foreach ($elements as $element) {
            $result = json_decode(
                Bold_Checkout_Service::call(
                    'DELETE',
                    self::LIFE_ELEMENTS_API_URI . '/' . $element,
                    $websiteId
                )
            );
            if (isset($result->errors)) {
                Mage::throwException('Cannot delete LiFE element on bold.');
            };
        }
        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');
        $session->addNotice(
            Mage::helper('core')->__('A total of %s (LiFE) Element(s) have been deleted.', count($elements))
        );
    }

    /**
     * Retrieve Life elements from Bold.
     *
     * @param int $websiteId
     * @return mixed
     * @throws Mage_Core_Exception
     */
    private function getLifeElementsFromBold($websiteId)
    {
        $result = json_decode(
            Bold_Checkout_Service::call('GET', self::LIFE_ELEMENTS_API_URI, $websiteId),
            true
        );
        if (isset($result['errors'])) {
            Mage::throwException('Cannot get life elements from bold');
        }
        return $result['data']['life_elements'];
    }

    /**
     * Create new LiFE elements.
     *
     * @param array $magentoLifeElements
     * @param array $metaFieldsToAdd
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    private function add(array $magentoLifeElements, array $metaFieldsToAdd, $websiteId)
    {
        $lifeElementsToAdd = [];
        foreach ($magentoLifeElements as $magentoLifeElement) {
            if (in_array($magentoLifeElement['meta_data_field'], $metaFieldsToAdd)) {
                $magentoLifeElement['input_required'] = (bool)$magentoLifeElement['input_required'];
                $magentoLifeElement['order_asc'] = (int)$magentoLifeElement['order_asc'];
                $lifeElementsToAdd[] = $magentoLifeElement;
            }
        }
        $this->createBoldLifeElements($websiteId, $lifeElementsToAdd);
    }

    /**
     * Update LiFE element on Bold side.
     *
     * @param array $magentoLifeElements
     * @param array $metaFieldsToUpdate
     * @param array $boldLifeElements
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public function update(
        array $magentoLifeElements,
        array $metaFieldsToUpdate,
        array $boldLifeElements,
        $websiteId
    ) {
        $lifeElementsToUpdate = [];
        foreach ($magentoLifeElements as $magentoLifeElement) {
            if (in_array($magentoLifeElement['meta_data_field'], $metaFieldsToUpdate)) {
                foreach ($boldLifeElements as $boldLifeElement) {
                    if ($boldLifeElement['meta_data_field'] === $magentoLifeElement['meta_data_field']) {
                        $magentoLifeElement['input_required'] = (bool)$magentoLifeElement['input_required'];
                        $magentoLifeElement['order_asc'] = (int)$magentoLifeElement['order_asc'];
                        $lifeElementsToUpdate[$boldLifeElement['public_id']] = $magentoLifeElement;
                    }
                }
            }
        }
        $this->updateBoldLifeElements($websiteId, $lifeElementsToUpdate);
    }

    /**
     * Delete LiFE elements from Bold.
     *
     * @param array $boldLifeElements
     * @param array $metaFieldsToDelete
     * @param int $websiteId
     * @return void
     * @throws Mage_Core_Exception
     */
    public function delete(array $boldLifeElements, array $metaFieldsToDelete, $websiteId)
    {
        $lifeElementsToDelete = [];
        foreach ($boldLifeElements as $boldLifeElement) {
            if (in_array($boldLifeElement['meta_data_field'], $metaFieldsToDelete)) {
                $lifeElementsToDelete[] = $boldLifeElement['public_id'];
            }
        }
        $this->deleteBoldLifeElements($websiteId, $lifeElementsToDelete);
    }
}
