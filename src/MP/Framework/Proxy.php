<?php

class Proxy
{
    private $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public function __call($method, $args)
    {
        // Run before code here
        //if (isset($this->before[$method]))
        //	call_user_func_array($this->before[$method], $args);

        // Invoke original method on our proxied object
        call_user_func_array(array($this->object, $method), $args);

        // Run after code here
        //if (isset($this->after[$method]))
        //	call_user_func_array($this->before[$method], $args);
    }
}
