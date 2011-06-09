<?php

require_once "sputnik/IDbAdapter.php";
 
class DbMySQLAdapter implements IDbAdapter {

	private $connection = null;

	/**
	 * @return string
	 */
	public function Info() {
		return mysql_get_server_info($this->connection);
	}

	/**
	 * @param  $string
	 * @return string
	 */
	public function EscapeString($string) {
		return mysql_real_escape_string($string);
	}

	/**
	 * @param  $query_string
	 * @return resource
	 */
	public function Query($query_string) {
		return mysql_query($query_string, $this->connection);
	}

	/**
	 * @param  $db
	 * @return void
	 */
	public function SwitchDb($db) {
		mysql_select_db($db, $this->connection);
	}

	/**
	 * @return void
	 */
	public function Disconnect() {
		if($this->connection)
			mysql_close($this->connection);
	}

	/**
	 * @param  $server
	 * @param  $username
	 * @param  $password
	 * @return resource the connection
	 */
	public function Connect($server, $username, $password) {
		$this->connection = mysql_connect($server, $username, $password);
		return $this->connection;
	}

	/**
	 * @return int
	 */
	public function GetAffectedRows() {

		return mysql_affected_rows($this->connection);
	}

	/**
	 * @return int
	 */
	public function GetInsertedId() {
		return mysql_insert_id($this->connection);
	}

	/**
	 * 
	 * @return string
	 */
	public function GetError() {
		return mysql_error($this->connection);
	}
}
