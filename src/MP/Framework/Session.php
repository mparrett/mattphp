<?php

namespace MP\Framework;

class Session
{
	var $fields = array();
	var $started = FALSE;

	function __construct()
	{
	}

	function __set($key, $value)
	{
		if (isset($_SESSION)) {
			$_SESSION[$key] = $value;
		}
	}

	function __get($key)
	{
		if (!isset($_SESSION))
			return NULL;

		return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : NULL;
	}

	function __isset($key)
	{
		if (!isset($_SESSION))
			return NULL;

		return array_key_exists($key, $_SESSION) && $_SESSION[$key] !== NULL;
	}

	function __unset($key)
	{
		if (!isset($_SESSION))
			return NULL;

		// Use array-splice rather than unset to stay Mongo-friendly

		$keys = array_keys($_SESSION);
		$offset = array_search($key, $keys);
		$_SESSION = array_slice($_SESSION, $offset, 1);
	}

	function start()
	{
		if ($this->started === FALSE) {
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
