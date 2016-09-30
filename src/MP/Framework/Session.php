<?php

namespace MP\Framework;

class Session
{
    public $fields = array();
    public $started = false;

    public function __construct()
    {
    }

    public function __set($key, $value)
    {
        if (isset($_SESSION)) {
            $_SESSION[$key] = $value;
        }
    }

    public function __get($key)
    {
        if (!isset($_SESSION)) {
            return null;
        }

        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null;
    }

    public function __isset($key)
    {
        if (!isset($_SESSION)) {
            return null;
        }

        return array_key_exists($key, $_SESSION) && $_SESSION[$key] !== null;
    }

    public function __unset($key)
    {
        if (!isset($_SESSION)) {
            return null;
        }

        // Use array-splice rather than unset to stay Mongo-friendly

        $keys = array_keys($_SESSION);
        $offset = array_search($key, $keys);
        $_SESSION = array_slice($_SESSION, $offset, 1);
    }

    public function start()
    {
        if ($this->started === false) {
            ini_set('session.save_handler', 'memcached');
            ini_set('session.gc_maxlifetime', 10800);
            ini_set('session.hash_function', 1);
            ini_set('session.hash_bits_per_character', 5);
            ini_set('session.save_path', 'localhost:11211');
            ini_set('memcached.sess_prefix', 'sessions.1');

            if (isset($_COOKIE[session_name()]) && session_id() === '') {
                $this->started = session_start();
            }
        }
    }
}
