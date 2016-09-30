<?php

namespace MP\Framework;

/**
 * MattPHP
 * Dependency Injection Container
 * In this framework, primarily enables lazy-loading and initialization
 * of intertwined dependencies
 * @author Matt Parrett
 */
class DI
{
    public $refs = array();

    /**
     * Initializes a factory at $name
     */
    public function set_factory($name, $ref)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Cannot overwrite $name");
        }

        $this->refs[$name] = array($ref, true);
    }

    /**
     * Initializes a singleton/value at $name
     */
    public function set($name, $ref)
    {
        if (isset($this->refs[$name])) {
            throw new \Exception("Cannot overwrite $name");
        }

        $this->refs[$name] = array($ref, false);
    }

    /**
     * Gets dependency at $name
     */
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
