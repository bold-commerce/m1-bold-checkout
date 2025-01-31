<?php

/**
 * Quote item entity to array extract service.
 *
 * @phpcs:disable Zend.NamingConventions.ValidVariableName.NotCamelCaps
 */
class Bold_Checkout_Service_Extractor_Quote_Item
{
    /**
     * Extract quote items data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    public static function extract(Mage_Sales_Model_Quote $quote)
    {
        $items = [];
        foreach ($quote->getAllItems() as $item) {
            if (!self::shouldAppearInCart($item)) {
                continue;
            }
            $price = $item->getParentItem()
                ? Mage::app()->getStore()->roundPrice($item->getParentItem()->getPrice())
                : Mage::app()->getStore()->roundPrice($item->getPrice());
            $productData = current(Bold_Checkout_Service_Extractor_Product::extract([$item->getProduct()]));

            $lineItem = new stdClass();
            $lineItem->item_id = (int)$item->getId();
            $lineItem->sku = $item->getProduct()->getData('sku');
            $lineItem->qty = $item->getParentItem() ? (int)$item->getParentItem()->getQty() : (int)$item->getQty();
            $lineItem->name = $item->getName();
            $lineItem->price = $price;
            $lineItem->product_type = $item->getProductType();
            $lineItem->quote_id = (string)$quote->getId();
            $lineItem->product_option = [
                'extension_attributes' => [
                    'custom_options' => self::extractCustomOptions($item->getParentItem() ?: $item),
                ],
            ];
            $lineItem->extension_attributes = [
                'product' => $productData,
                'tax_details' => self::extractTaxDetails($item->getParentItem() ?: $item, $quote),
                'bold_discounts' => self::getDiscountData($item->getParentItem() ?: $item),
            ];

            Mage::dispatchEvent('bold_checkout_quote_item_extract_after', ['item' => $lineItem, 'quote_item' => $item]);
            $items[] = $lineItem;
        }

        return $items;
    }

    /**
     * Extract quote items data.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     * @throws Mage_Core_Exception
     */
    public static function extractLineItems(Mage_Sales_Model_Quote $quote)
    {
        $lineItems = [];
        foreach ($quote->getAllItems() as $cartItem) {
            if (static::shouldAppearInCart($cartItem)) {
                $lineItems[] = self::extractLineItem($cartItem);
            }
        }
        if (!$lineItems) {
            Mage::throwException(
                Mage::helper('core')->__('There are no cart items to checkout.')
            );
        }
        return $lineItems;
    }

    /**
     * Extract quote item entity data into array.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return stdClass
     */
    private static function extractLineItem(Mage_Sales_Model_Quote_Item $item)
    {
        $lineItem = new stdClass();
        $lineItem->id = (int)$item->getProduct()->getId();
        $lineItem->quantity = $item->getParentItem() ? (int)$item->getParentItem()->getQty() : (int)$item->getQty();
        $lineItem->title = self::getLineItemName($item);
        $lineItem->product_title = self::getLineItemName($item);
        $lineItem->weight = self::getLineItemWeightInGrams($item);
        $lineItem->taxable = true; // Doesn't matter since RSA will handle taxes
        $lineItem->image = self::getLineItemImage($item);
        $lineItem->requires_shipping = !$item->getIsVirtual();
        $lineItem->line_item_key = (string)$item->getId();
        $lineItem->price = self::getLineItemPrice($item);
        Mage::dispatchEvent('bold_checkout_line_item_extract_after', ['line_item' => $lineItem, 'quote_item' => $item]);

        return $lineItem;
    }

    /**
     * Check if quote item should appear in Bold cart.
     *
     * @param Mage_Sales_Model_Quote_Item $cartItem
     * @return bool
     */
    public static function shouldAppearInCart(Mage_Sales_Model_Quote_Item $cartItem)
    {
        $parentItem = $cartItem->getParentItem();
        $parentIsBundle = $parentItem && $parentItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE;
        return (!$cartItem->getChildren() && !$parentIsBundle)
            || $cartItem->getProductType() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE;
    }

    /**
     * Gets the product's name from the line item
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    private static function getLineItemName(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return $item->getName();
    }

    /**
     * Gets the product's weight in grams from the line item
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return float
     */
    private static function getLineItemWeightInGrams(Mage_Sales_Model_Quote_Item $item)
    {
        /** @var Bold_Checkout_Model_Config $config */
        $config = Mage::getSingleton(Bold_Checkout_Model_Config::RESOURCE);
        $conversionRate = $config->getWeightConversionRate((int)$item->getQuote()->getStore()->getWebsiteId());
        $weight = $item->getWeight();
        return $weight ? round($weight * $conversionRate, 2) : 0;
    }

    /**
     * Gets the product's image from the line item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return string
     */
    private static function getLineItemImage(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        $image = $item->getProduct()->getThumbnail();
        /** @var Mage_Catalog_Helper_Image $imageHelper */
        $imageHelper = Mage::helper('catalog/image');
        return $image ? (string)$imageHelper->init($item->getProduct(), 'thumbnail') : '';
    }

    /**
     * Gets the product's price in cents from the line item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return int
     */
    private static function getLineItemPrice(Mage_Sales_Model_Quote_Item $item)
    {
        $item = $item->getParentItem() ?: $item;
        return (int)round((float)$item->getPrice() * 100);
    }

    /**
     * Extract tax details for quote item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    private static function extractTaxDetails(Mage_Sales_Model_Quote_Item $item, Mage_Sales_Model_Quote $quote)
    {
        if ($item->getProductType() === 'bundle') {
            $itemTaxDetails = [];
            foreach ($item->getChildren() as $childItem) {
                $itemTaxData = self::getTaxData($quote, $childItem);
                foreach ($itemTaxDetails as $itemTaxDetail) {
                    foreach ($itemTaxData as $index => $itemTaxDataItem) {
                        if ($itemTaxDetail['id'] === $itemTaxDataItem['id']) {
                            unset($itemTaxData[$index]);
                        }
                    }
                }
                $itemTaxDetails = array_merge($itemTaxDetails, $itemTaxData);
            }
            return $itemTaxDetails;
        }
        return self::getTaxData($quote, $item);
    }

    /**
     * Extract custom options for quote item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    private static function extractCustomOptions(Mage_Sales_Model_Quote_Item $item)
    {
        $product = $item->getProduct();
        $options = [];
        $optionIds = $item->getOptionByCode('option_ids');
        if (!$optionIds) {
            return $options;
        }
        foreach (explode(',', $optionIds->getValue()) as $optionId) {
            $option = $product->getOptionById($optionId);
            if ($option) {
                $itemOption = $item->getOptionByCode('option_' . $option->getId());
                $options[] = [
                    'option_id' => $option->getId(),
                    'option_value' => $itemOption->getValue(),
                ];
            }
        }
        $addOptions = $item->getOptionByCode('additional_options');
        if ($addOptions) {
            $options = array_merge($options, unserialize($addOptions->getValue()));
        }
        return $options;
    }

    /**
     * Get tax for quote item.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     */
    private static function getTaxData(
        Mage_Sales_Model_Quote $quote,
        Mage_Sales_Model_Quote_Item $item
    ) {
        $itemTaxDetails = new stdClass();
        $itemTaxDetails->taxes = [];
        $itemTaxAmount = $item->getTaxAmount();
        foreach ($quote->getTaxesForItems() as $itemId => $taxDetails) {
            if ((int)$item->getId() !== (int)$itemId) {
                continue;
            }
            $appliedTaxNumber = count($taxDetails);
            $i = 1;
            foreach ($taxDetails as $tax) {
                $calculatedAmount = (float)$item->getPrice() * ($tax['percent'] / 100);
                $amount = $i < $appliedTaxNumber && $calculatedAmount < $itemTaxAmount
                    ? $calculatedAmount
                    : $itemTaxAmount;
                $itemTaxAmount = $itemTaxAmount - $amount;
                $taxData = new stdClass();
                $taxData->id = $tax['id'];
                $taxData->amount = $calculatedAmount;
                $taxData->percent = $tax['percent'];
                $taxData->rates = $tax['rates'];
                $itemTaxDetails->taxes[] = $taxData;
                $i++;
            }
        }
        Mage::dispatchEvent(
            'bold_checkout_item_tax_extract_after',
            [
                'item_tax_details' => $itemTaxDetails,
                'quote_item' => $item,
                'quote' => $quote,
            ]
        );
        return array_values($itemTaxDetails->taxes);
    }

    /**
     * Extract discount data for quote item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getDiscountData(Mage_Sales_Model_Quote_Item $item)
    {
        if ($item->getProductType() === 'bundle') {
            return self::getBundleDiscounts($item);
        }
        $ruleIds = $item->getAppliedRuleIds();
        if (!$ruleIds) {
            return [];
        }
        $ruleIds = explode(',', $ruleIds);
        $rule = Mage::getModel('salesrule/rule')->load($ruleIds[0]);
        return [
            [
                'discount_data' => [
                    'amount' => Mage::app()->getStore()->roundPrice($item->getDiscountAmount()),
                    'base_amount' => Mage::app()->getStore()->roundPrice($item->getBaseDiscountAmount()),
                    'original_amount' => Mage::app()->getStore()->roundPrice($item->getOriginalDiscountAmount()),
                    'base_original_amount' => Mage::app()->getStore()->roundPrice($item->getBaseOriginalDiscountAmount()),
                ],
                'rule_label' => $item->getQuote()->getCouponCode()
                    ? $item->getQuote()->getCouponCode()
                    : Mage::helper('core')->__('Discount'),
                'rule_id' => (int)$rule->getId(),
            ],
        ];
    }

    /**
     * Extract discount data for bundle quote item.
     *
     * @param Mage_Sales_Model_Quote_Item $item
     * @return array[]
     * @throws Mage_Core_Model_Store_Exception
     */
    private static function getBundleDiscounts(
        Mage_Sales_Model_Quote_Item $item
    ) {
        $amount = 0;
        $baseAmount = 0;
        $originalAmount = 0;
        $baseOriginalAmount = 0;
        $ruleId = null;
        foreach ($item->getChildren() as $childItem) {
            $ruleIds = $childItem->getAppliedRuleIds();
            if (!$ruleIds) {
                continue;
            }
            $ruleIds = explode(',', $ruleIds);
            $rule = Mage::getModel('salesrule/rule')->load($ruleIds[0]);
            $ruleId = (int)$rule->getId();
            $amount += $childItem->getDiscountAmount();
            $baseAmount += $childItem->getBaseDiscountAmount();
            $originalAmount += $childItem->getOriginalDiscountAmount();
            $baseOriginalAmount += $childItem->getBaseOriginalDiscountAmount();
        }
        if ($amount === 0) {
            return [];
        }
        return [
            [
                'discount_data' => [
                    'amount' => Mage::app()->getStore()->roundPrice($amount),
                    'base_amount' => Mage::app()->getStore()->roundPrice($baseAmount),
                    'original_amount' => Mage::app()->getStore()->roundPrice($originalAmount),
                    'base_original_amount' => Mage::app()->getStore()->roundPrice($baseOriginalAmount),
                ],
                'rule_label' => $item->getQuote()->getCouponCode()
                    ? $item->getQuote()->getCouponCode()
                    : Mage::helper('core')->__('Discount'),
                'rule_id' => $ruleId,
            ],
        ];
    }
}
