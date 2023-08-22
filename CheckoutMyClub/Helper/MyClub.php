<?php

/**
 * Adapt catalog my club helper rewrite.
 */
class Bold_CheckoutMyClub_Helper_MyClub extends Mage_Catalog_Helper_Myclub
{
    /**
     * @var string|null
     */
    private $clubId;

    /**
     * Store club id in local cache.
     *
     * @param string $clubId
     * @return void
     */
    public function addClubId($clubId)
    {
        $this->clubId = $clubId;
    }

    /**
     * Rewrite to retrieve "Club_id" from local cache.
     *
     * @param string $key
     * @return string
     */
    public function registry($key)
    {
        if ($key !== 'Club_id') {
            return parent::registry($key);
        }

        return $this->clubId ?: parent::registry($key);
    }
}
