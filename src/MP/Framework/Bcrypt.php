<?php

namespace MP\Framework;

/**
 * Bcrypt algorithm using crypt() function of PHP
 * Ripped from Zend Framework and modified
 */
class Bcrypt
{
    protected $cost;
    protected $salt;
    
    /**
     * @param  string $salt
     * Changed from 14 to 10 to prevent possibile DOS attacks
     * due to the high computational time
     * @see http://timoh6.github.io/2013/11/26/Aggressive-password-stretching.html
     */
    public function __construct($salt = null, $cost = 10)
    {
        if ($salt === null) {
            $salt = self::getRandomSalt();
        }

        if (strlen($salt) < 16) {
            throw new \Exception("Salt must be at least 16 bytes long");
        }

        if ($cost < 4 || $cost > 31) {
            throw new Exception\InvalidArgumentException(
                'The cost parameter of bcrypt must be in range 04-31'
            );
        }

        $this->salt = $salt;
        $this->cost = sprintf('%1$02d', $cost);
    }

    /**
     * Bcrypt encrypt a password
     *
     * @param  string $password
     * @throws Exception\RuntimeException
     * @return string
     */
    public function create($password)
    {
        $salt64 = substr(str_replace('+', '.', base64_encode($this->salt)), 0, 22);

        // We suggest to upgrade to PHP 5.3.7+ OR use passwords with only 7-bit characters
        $hash = crypt($password, '$2y$' . $this->cost . '$' . $salt64);

        if (strlen($hash) < 13) {
            throw new Exception\RuntimeException('Error during the bcrypt generation');
        }
        return $hash;
    }

    /**
     * Verify if a password is correct against a hash value
     *
     * @param  string $password
     * @param  string $hash
     * @throws Exception\RuntimeException when the hash is unable to be processed
     * @return bool
     */
    public function verify($password, $hash)
    {
        $result = crypt($password, $hash);
        if ($result === $hash) {
            return true;
        }
        return false;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Get a random salt of specified length
     */
    public static function getRandomSalt($size = 16)
    {
        $ret = openssl_random_pseudo_bytes($size, $strong);
        if (!$strong) {
            throw new \Exception("Can't getRandomSalt, broken crypto?");
        }
        return $ret;
    }
}
