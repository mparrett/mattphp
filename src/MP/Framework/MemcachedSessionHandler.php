<?php

namespace MP\Framework;

/**
 * Inspired by Symfony2
 * TODO: Use Symfony2's handler directly
 */
class MemcachedSessionHandler implements \SessionHandlerInterface
{
    private $memcached;
    private $ttl;
    private $prefix;

    public function __construct(\Memcached $memcached, array $options = array())
    {
        $this->memcached = $memcached;

        if ($diff = array_diff(array_keys($options), array('prefix', 'expiretime'))) {
            throw new \InvalidArgumentException(sprintf(
                'The following options are not supported "%s"', implode(', ', $diff)
            ));
        }

        $this->ttl = isset($options['expiretime']) ? (int) $options['expiretime'] : 86400;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sf2s';
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($sessionId)
    {
        return $this->memcached->get($this->prefix.$sessionId) ?: '';
    }

    public function write($sessionId, $data)
    {
        return $this->memcached->set($this->prefix.$sessionId, $data, time() + $this->ttl);
    }

    public function destroy($sessionId)
    {
        return $this->memcached->delete($this->prefix.$sessionId);
    }

    public function gc($maxlifetime)
    {
        // not required here because memcached will auto expire the records anyhow.
        return true;
    }

    protected function getMemcached()
    {
        return $this->memcached;
    }
}
