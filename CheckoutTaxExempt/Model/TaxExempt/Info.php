<?php

/**
 * Tax exempt info data model.
 */
class Bold_CheckoutTaxExempt_Model_TaxExempt_Info
{
    const RESOURCE = 'bold_checkouttaxexempt/taxexempt_info';

    /**
     * @var string|null
     */
    private $fileUrl;

    /**
     * @var string|null
     */
    private $comment;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @param array $taxExemptInfo
     */
    public function __construct(array $taxExemptInfo)
    {
        $this->fileUrl = isset($taxExemptInfo['file_url']) ? $taxExemptInfo['file_url'] : null;
        $this->file = isset($taxExemptInfo['file']) ? $taxExemptInfo['file'] : null;
        $this->comment = isset($taxExemptInfo['comment']) ? $taxExemptInfo['comment'] : null;
    }

    /**
     * Get tax-exempt comment.
     *
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Get tax-exempt document name.
     *
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get tax-exempt document url.
     *
     * @return string|null
     */
    public function getFileUrl()
    {
        return $this->fileUrl;
    }
}
