<?php

namespace MP\Framework;

class DI
{
    public $refs = array();

    public function set_factory($name, $ref)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Cannot overwrite $name");
        }

        $this->refs[$name] = array($ref, true);
    }

    public function set($name, $ref)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Cannot overwrite $name");
        }

        $this->refs[$name] = array($ref, false);
    }

    public function get($name)
    {
        if (!isset($this->refs[$name])) {
            return null;
        }

        list($ref, $factory) = $this->refs[$name];

        if ($factory) {
            return $ref();
        }

        if (is_callable($ref)) {
            $this->refs[$name] = array($ref(), $factory);
        }

        return $this->refs[$name][0];
    }
}
