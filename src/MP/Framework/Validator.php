<?php

namespace MP\Framework;

class Validator {
	var $request;
	function __construct($request) {
		$this->request = $request;
	}

	function requireSet() {
		$args = func_get_args();
		foreach($args as $arg)
			if (!isset($this->request->request[$arg]))
				throw new \Exception("Missing required argument: $arg");
	}
}
