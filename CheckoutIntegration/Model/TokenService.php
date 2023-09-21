<?php

/**
 * Token service.
 */
class Bold_CheckoutIntegration_Model_TokenService
{
    /**
     * Lengths of token fields
     */
    const LENGTH_TOKEN = 32;
    const LENGTH_TOKEN_SECRET = 32;
    const LENGTH_TOKEN_VERIFIER = 32;

    /**
     * Lengths of consumer fields
     */
    const LENGTH_CONSUMER_KEY = 32;
    const LENGTH_CONSUMER_SECRET = 32;

    /**
     * Nonce length
     */
    const LENGTH_NONCE = 32;

    /**
     * Value of callback URL when it is established or if the client is unable to receive callbacks
     *
     * @link http://tools.ietf.org/html/rfc5849#section-2.1     Requirement in RFC-5849
     */
    const CALLBACK_ESTABLISHED = 'oob';

    /**
     * Generate random string for token or secret or verifier
     *
     * @param int $length String length
     * @return string
     */
    public static function generateRandomString($length)
    {
        return Mage::helper('core')->getRandomString(
            $length,
            Mage_Core_Helper_Data::CHARS_DIGITS . Mage_Core_Helper_Data::CHARS_LOWERS
        );
    }

    /**
     * Generate random string for token
     *
     * @return string
     */
    public static function generateToken()
    {
        return self::generateRandomString(self::LENGTH_TOKEN);
    }

    /**
     * Generate random string for token secret
     *
     * @return string
     */
    public static function generateTokenSecret()
    {
        return self::generateRandomString(self::LENGTH_TOKEN_SECRET);
    }

    /**
     * Generate random string for verifier
     *
     * @return string
     */
    public static function generateVerifier()
    {
        return self::generateRandomString(self::LENGTH_TOKEN_VERIFIER);
    }

    /**
     * Generate random string for consumer key
     *
     * @return string
     */
    public static function generateConsumerKey()
    {
        return self::generateRandomString(self::LENGTH_CONSUMER_KEY);
    }

    /**
     * Generate random string for consumer secret
     *
     * @return string
     */
    public static function generateConsumerSecret()
    {
        return self::generateRandomString(self::LENGTH_CONSUMER_SECRET);
    }
}
