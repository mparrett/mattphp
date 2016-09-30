<?php

namespace MP\Framework;

/**
 * Validate GET/POST request arguments
 */
class Validator
{
    public $request;
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function requireSet()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if (!isset($this->request->request[$arg])) {
                throw new \Exception("Missing required argument: $arg");
            }
        }
    }
}
