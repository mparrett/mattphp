<?php

namespace MP\Framework;

/**
 * Inspired by RafalFilipek's wrapper
 * Wraps a memcached instance and provides a few helpful methods
 */
class MemcachedWrapper
{
    protected $memcached;

    public function __construct($memcached)
    {
        $this->memcached = $memcache;
    }

    /**
     * Get an item, or fallback (and set)
     */
    public function get($key, $fallback = null, $expiration = 0)
    {
        $result = $this->memcached->get($key);

        if ((false === $result || null === $result) && $fallback instanceof \Closure) {
            $result = $fallback();
            $this->memcached->set($key, $result, $expiration);
        }
        return $result;
    }

    public function set($key, $data, $expiration = 0)
    {
        return $this->memcached->set($key, $data, $expiration);
    }

    public function delete($key, $time = 0)
    {
        return $this->memcached->delete($key, $time);
    }

    // Use with caution
    public function flush()
    {
        //return $this->memcached->flush();
    }
}
