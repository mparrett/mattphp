<?php

namespace MP\Framework;

/**
 * MattPHP
 * Basic data model/container. Each object corresponds with a database row.
 * Tracks whether an object is dirty and needs to be saved. Performs insert/update.
 * @author Matt Parrett
 */
class Model
{
    public $fields = array();
    public $dirty = array();

    public $primary = 'id';    // Default is 'id'
    public $table;                // Default is class name
    public $db;

    public function __construct(&$db)
    {
        $this->db = $db;

        // Automatically set table based on class name
        $this->table = \MP\Framework\Inflector::pluralize(
            strtolower(join('', array_slice(explode('\\', get_class($this)), -1)))
        );
    }

    public function __get($field)
    {
        return isset($this->fields[$field]) ? $this->fields[$field] : null;
    }

    public function __set($field, $val)
    {
        if (!isset($this->fields[$field]) || $this->fields[$field] !== $val) {
            $this->dirty[$field] = $val;
        }

        return $this->fields[$field] = $val;
    }

    public function __isset($field)
    {
        return isset($this->fields[$field]);
    }

    public function __unset($field)
    {
        if (isset($this->fields[$field])) {
            $this->dirty[$field] = null;

            unset($this->fields[$field]);
        }
    }

    public function load_data($data)
    {
        $this->fields = $data;
    }

    public function get_data()
    {
        return $this->fields;
    }

    /**
     * Load from DB
     */
    public function load($primary, $fields = '*')
    {
        $q = "SELECT $fields FROM ".$this->table." WHERE ".
            $this->db->escapeField($this->primary)." = ".$this->db->escapeValue($primary);

        $row = $this->db->queryOne($q);
        if (!$row) {
            return false;
        }

        $this->fields = $row;
        return true;
    }

    /**
     * Save to DB (upsert)
     */
    public function save()
    {
        if (empty($this->dirty)) {
            return;
        }

        $save_data = array_merge(
            array($this->primary => $this->{$this->primary}),
            $this->dirty
        );

        $this->db->queryAll($this->db->buildInsert($this->table, $save_data, $this->primary));
        $id = $this->db->insertId();
        if ($id !== 0) {
            $this->fields[$this->primary] = $id;
        }
    }

    /**
     * Delete from DB
     */
    public function delete()
    {
        $this->db->delete($this->table, $this->primary, $this->{$this->primary});
    }
}
