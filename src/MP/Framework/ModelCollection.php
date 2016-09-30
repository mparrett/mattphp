<?php


namespace MP\Framework;

/**
 * Matt PHP
 * Collection of Models.
 * In other words, a typed collection of $className
 * @author Matt Parrett
 */
class ModelCollection implements ArrayAccess, Iterator, Countable
{
    private $instances = array();
    public $className;
    public $db;

    public function __construct($db, $className)
    {
        $this->db_adapter = $db;
        $this->className = $className;
    }

    public function __toString()
    {
        $out = "[" . $this->className . " (" . count($this->instances) . ")]\n";

        foreach ($this->instances as $key => $value) {
            $out .= " $key = $value\n";
        }
        return $out;
    }

    public function offsetSet($offset, $value)
    {
        if ($value instanceof $this->className) {
            if ($offset == "") {
                $this->instances[] = $value;
            } else {
                $this->instances[$offset] = $value;
            }
        } else {
            throw new Exception("Value has to be a instance of $this->className");
        }
    }

    // Sort of a multi-factory
    public function fromArray($array)
    {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as $key => $value) {
            $instance = new $this->className($this->db);

            if ($value) {
                $instance->load_data($value);
            }

            $this->instances[$key] = $instance;
        }
    }

    public function toArray()
    {
        $ret = array();
        foreach ($this->instances as $key => $model) {
            $ret[$key] = $model->get_data();
        }
        return $ret;
    }

    public function offsetExists($offset)
    {
        return isset($this->instances[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->instances[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->instances[$offset]) ? $this->instances[$offset] : null;
    }

    public function rewind()
    {
        reset($this->instances);
    }

    public function current()
    {
        return current($this->instances);
    }

    public function key()
    {
        return key($this->instances);
    }

    public function next()
    {
        return next($this->instances);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function count()
    {
        return count($this->instances);
    }
}
