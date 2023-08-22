<?php

/**
 * Multi fees quote data model.
 */
class Bold_CheckoutMageWorxMultiFees_Model_Quote_Fees_Data extends Mage_Core_Model_Abstract
{
    const RESOURCE = 'bold_checkout_mageworx_multifees/quote_fees_data';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(self::RESOURCE);
    }

    /**
     * Set quote entity id.
     *
     * @param int $quoteId
     * @return void
     */
    public function setQuoteId($quoteId)
    {
        $this->setData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID, $quoteId);
    }

    /**
     * Retrieve quote id.
     *
     * @return int|null
     */
    public function getQuoteId()
    {
        return $this->getData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID)
            ? (int)$this->getData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::QUOTE_ID)
            : null;
    }

    /**
     * Set fees data entity id.
     *
     * @param string $feesData
     * @return void
     */
    public function setFeesData($feesData)
    {
        $this->setData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::DATA, $feesData);
    }

    /**
     * Retrieve fees data.
     *
     * @return string|null
     */
    public function getFeesData()
    {
        return $this->getData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::DATA)
            ? (string)$this->getData(Bold_CheckoutMageWorxMultiFees_Model_Resource_Quote_Fees_Data::DATA)
            : null;
    }
}
