<?php

namespace MP\Framework;

////
// Simple MySQL DB Wrapper and Abstraction
////

class DB {
	var $mysqli;
	var $port;
	var $host;
	var $user;
	var $password;
	var $dbname;
	var $use_compression;
	var $driver;

	var $instance;  // DEBUG
	static $next_instance = 0;  // DEBUG

	function __construct($host, $user, $password, $dbname = null, $port = 3306, $use_compression = false)
	{
		$this->host             = $host;
		$this->user             = $user;
		$this->password         = $password;
		$this->dbname           = $dbname;
		$this->port             = $port;
		$this->use_compression  = $use_compression;

		$this->instance = self::$next_instance++; // DEBUG
		//error_log("DB CONSTRUCTOR $host $user $password $dbname $port $use_compression");

		$res = $this->connect();
	}

	/*
	function connect_pdo() {
		$dsn = 'mysql:dbname='.$this->dbname.';host='.$this->host;
		$this->mysqli = new \PDO( $dsn, $this->user, $this->password );
	}
	*/

	function connect()
	{
		if ($this->use_compression) {
			$this->mysqli = mysqli_init();
			$this->mysqli->real_connect($this->host, $this->user, $this->password, $this->dbname, $this->port, MYSQLI_CLIENT_COMPRESS);
		} else {
			$this->mysqli = new \mysqli($this->host, $this->user, $this->password, $this->dbname, $this->port);
		}

		if ($this->mysqli->connect_errno) {
			error_log("Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);

			return false;
		}

		$this->connected = true;

		return true;
	}

	private function refValues($arr)
	{
		$refs = array();
		foreach($arr as $key => $value)
			$refs[$key] = &$arr[$key];
		return $refs;
	}

	public function prep_stmt($sql, $types = null, $params = null)
	{
		$stmt = $this->mysqli->prepare($sql);

		if (!$stmt)
			throw new \Exception("Could not prepare '$sql': ".$this->mysqli->errno.' '.$this->mysqli->error);

		if ($params !== null && $types !== null) {

			$refs = array();
			foreach($params as $k => $v)
				$refs[$k] = &$params[$k];

			array_unshift($refs, $types);

			$res = call_user_func_array(array($stmt, 'bind_param'), $refs);

			if ($res === false) {
				throw new \Exception("Could not bind params ({$types[$idx]}, $p) for '$sql': ".$stmt->errno.' '.$stmt->error.' '.json_encode($types).' '.json_encode($params));
			}

		}

		return $stmt;
	}

	public function exec_stmt($sql, $types = null, $params = null)
	{
		$stmt = $this->prep_stmt($sql, $types, $params);

		$res = $stmt->execute();

		if (!$res) {
			// TODO: Maybe don't throw an exception?
			throw new \Exception("Could not execute query '$sql': ".$stmt->errno.' '.$stmt->error);
		}

		$affected_rows = $stmt->affected_rows;

		$stmt->close();

		return $affected_rows;
	}

	public function query_field($q)
	{
		$result = $this->mysqli->query($q);
		if ($result === FALSE)
			throw new \Exception("Could not query field: ".$q);

		$out = array();
		$row = $result->fetch_array(MYSQLI_NUM);
		$result->free();

		return isset($row[0]) ? $row[0] : null;
	}

	public function queryOne($q, $resulttype = MYSQLI_ASSOC)
	{
		$result = $this->mysqli->query($q);
		if ($result === FALSE)
			return FALSE;

		$out = array();
		$row = $result->fetch_array($resulttype);
		$result->free();

		return $row;
	}

	function query_all_typed($q, $resulttype = MYSQLI_ASSOC)
	{
		$stmt = $this->mysqli->prepare($q);
		$stmt->execute();
		$res = $stmt->get_result();

		// XXX: MySQL Native Driver Only
		return $res->fetch_all($resulttype);
	}

	/*
	 * Query and return all rows in the specified format
	 * OR run an update/insert query and return affected rows or false
	 * returns FALSE on failure
	 */
	function queryAll($q, $resulttype = MYSQLI_ASSOC)
	{

		$result = $this->mysqli->query($q);

		/*
		 * Returns FALSE on failure.
		 * For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries
		 * mysqli_query() will return a mysqli_result object. For other successful queries
		 * mysqli_query() will return TRUE.
		 */

		if (TRUE === $result) {

			// Returns the number of affected rows on success, and -1 if the last query failed.

			$mar = $this->mysqli->affected_rows;

			return ($mar === -1) ? false : $mar;
		}

		if (FALSE === $result) {
			throw new \Exception('Query failed: '.$this->error().' '.$q);
		}

		// XXX: MySQL Native Driver Only
		return $result->fetch_all($resulttype);
	}

	function filter($q, $f, $resulttype = MYSQLI_ASSOC)
	{
		$result = $this->mysqli->query($q);
		if ($result === FALSE) return FALSE;

		$out = array();
		while ($row = $result->fetch_array($resulttype)) {
			if ($f($row))
				$out[] = $row;
		}

		$result->free();

		return $out;
	}

	function map($q, $f, $resulttype = MYSQLI_ASSOC)
	{
		$result = $this->mysqli->query($q);
		if ($result === FALSE) return FALSE;

		$out = array();
		while ($row = $result->fetch_array($resulttype))
			$out[] = $f($row);

		$result->free();

		return $out;
	}

	function reduce($q, $f, $initial = null, $resulttype = MYSQLI_ASSOC)
	{
		$result = $this->mysqli->query($q);
		if ($result === FALSE) return FALSE;

		$idx = 0;
		$carry = $initial;
		while ($row = $result->fetch_array($resulttype))
			$f($carry, $row, $idx++);

		$result->free();

		return $carry;
	}

	public function deleteOne($table, $primary_key, $val) {
		$this->exec_stmt("DELETE FROM `".$table."` WHERE `".$primary_key."` = ? LIMIT 1", 'i', array($val));
	}

	public function truncate($table) {
		$this->exec_stmt("TRUNCATE TABLE `".$table."`");
	}

	function escapeField($field)
	{
		return "`".$this->escape($field)."`";
	}

	function escapeValue($val)
	{
		if (NULL === $val)
			return 'NULL';

		if (ctype_digit($val))
			return $this->escape($val);
		else
			return "'".$this->escape($val)."'";
	}

	function escape($value)
	{
		return $this->mysqli->real_escape_string($value);
	}

	function buildInsert($table, $data, $primary = 'id')
	{
		// Seperate keys and values
		$keys = array_keys($data);
		$vals = array_values($data);

		// Escape fields and values
		$fields = array_map(array($this, 'escapeField'), $keys);
		$values = array_map(array($this, 'escapeValue'), $vals);

		$that = $this;

		// Update $data, replacing each item with key=value
		// Skip the primary key
		array_walk($data, function(&$item, $key) use (&$that, $primary) {
			if ($key === $primary)
				return;
			$item = $that->escapeField($key) . '=' . $that->escapeValue($item);
		}, $vals);

		unset($data[$primary]);

		// Upsert
		$query = "INSERT INTO $table (".implode(',', $fields).")";
		$query .= " VALUES (".implode(',', $values).")";
		$query .= " ON DUPLICATE KEY UPDATE ".implode(',', $data);

		return $query;
	}

	function errno()
	{
		return $this->mysqli->errno;
	}

	function error()
	{
		return $this->mysqli->error;
	}

	function close()
	{
		return $this->mysqli->close();
	}

	function insertId()
	{
		// If the last query wasn't an INSERT or UPDATE statement or if the modified
		// table does not have a column with the AUTO_INCREMENT attribute, this
		// function will return zero.

		return $this->mysqli->insert_id;
	}
}
