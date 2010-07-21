<?php

/**
 * A basic DB connection class returning
 * resultset objects following an iterator pattern
 * @author d11wtq
 */

require_once "DbResult.php";
require_once "DbRow.php";
require_once "config/config.php";

class Db {
	/**
	 * The database connection resource
	 * @var resource db
	 */
	private $conn;
	/**
	 * The database name itself
	 * @var string database
	 */
	private $db;
	/**
	 * An instance of a singleton
	 * @var object DB
	 */
	private static $instance = null;

	private $is_prepared = false;
	private $prep_vars = array();

	/**
	 * Constructor
	 * @param string server
	 * @param string username
	 * @param string password
	 * @param string db name
	 */
	public function __construct($host, $user, $pass, $db=false) {
		$this->ConnectTo($host, $user, $pass);
		if ($this->conn && $db) $this->SelectDatabase($db);
	}

	public function  __set($name,  $value) {
		$this->is_prepared = true;
		$this->prep_vars[$name] = $value;
	}


	public function __destruct() {
		$this->Disconnect();
	}

	/**
	 * Used for retreiving an instance of a singleton if wanted
	 * @deprecated since version 3.0 in favor for GetInstance
	 * @return object DB
	 */
	/*public static function getInstance($host=null, $user=null, $pass=null, $db=null) {
		return Db::GetInstance($host, $user, $pass, $db);
	}*/

	/**
	 * Used for retreiving an instance of a singleton if wanted
	 * @global <type> $config
	 * @param <type> $host
	 * @param <type> $user
	 * @param <type> $pass
	 * @param <type> $db
	 * @return <type> 
	 */
	public static function GetInstance($host=null, $user=null, $pass=null, $db=null) {
		global $config;
		if (self::$instance === null) {
			if ($host == null)
				self::$instance = new Db($config["db_connect"], $config["db_username"], $config["db_password"], $config["db_dbname"]);
			else
				self::$instance = new Db($host, $user, $pass, $db);
		}
		return self::$instance;
	}

	/**
	 * Connect to database (stored internally)
	 * @param string server
	 * @param string username
	 * @param string password
	 */
	public function ConnectTo($host, $user, $pass) {
		$this->conn = mysql_connect($host, $user, $pass) or die("Csatlakozási hiba: " . mysql_error());
		//$this->Switch_Utf8();
	}
	/**
	 * Change databases
	 * @param string database
	 */
	public function SelectDatabase($db) {
		@mysql_select_db($db, $this->conn);
		$this->db = $db;
	}

	/**
	 * Switch To Utf-8 encoding
	 * @param string database
	 */
	public function Switch_Utf8() {
		mysql_query("SET CHARACTER SET utf8");
	}

	/**
	 * Check which db is currently used
	 * @return string database
	 */
	public function GetDbName() {
		return $this->db;
	}
	/**
	 * Check if the connection is successful
	 * @return boolean
	 */
	public function IsConnected() {
		return is_resource($this->conn);
	}
	/**
	 * Close the connection
	 */
	public function Disconnect() {
		@mysql_close($this->conn);
	}
	/**
	 * Fetch the last error
	 * @return string error
	 */
	public function GetError() {
		return mysql_error($this->conn);
	}

	/**
	 *
	 * @param <type> $groups
	 * @return <type>
	 */
	private function ReplaceCallback($groups) {
		//print_r($groups);
		$val = $this->prep_vars[$groups[1]];
		if (get_magic_quotes_gpc()) $val = stripslashes($val);
		$val = mysql_real_escape_string($val);
		if (empty($val)) $val = "";
		return "'". $val ."'";
	}

	/**
	 *
	 * @param <type> $query
	 * @return <type> 
	 */
	private function PrepareQuery($query) {
		
		if ($this->is_prepared == false) return $query;
		$func_string = '';
		$result = preg_replace_callback('/\{([a-zA-Z0-9_]+?)\}/', array($this, ReplaceCallback), $query);
		return $result;
	}

	/**
	 * Run a query against the database and return
	 * a resultset iterator object
	 * @return object DB_Result
	 */
	public function Query($query, $send_in_utf8 = false) {
		$uselimit = false;
		// Engedélyezzük az SQL_CALC_FOUND_ROWS -t ha van benne LIMIT rész
		$tokens = explode(' ', strtolower($query));
		if (in_array("limit", $tokens) && $tokens[0] == "select") {
			$query = str_replace("select", "select sql_calc_found_rows", strtolower($query));
			$uselimit = true;
		}

		if ($send_in_utf8 == true) {
			$this->Switch_Utf8();
		}

		if ($this->is_prepared) $query = $this->PrepareQuery($query);
		$result = mysql_query($query, $this->conn) or
			   trigger_error("SQL Hiba: " . mysql_error() . "<br /><b>" . $query . "</b>", E_USER_ERROR);

		if ($this->is_prepared) {
			$this->prep_vars = array();
			$this->is_prepared = false;
		}
		if (count($tokens) > 0) {
			if (!in_array(trim($tokens[0]), array('update', 'delete', 'insert', 'replace'))) {
				//$insert = true;
				return new DbResult($result, $this->conn, $insert, $uselimit);
			} elseif(trim($tokens[0]) == "insert")
				return mysql_insert_id($this->conn);
		}
		return mysql_affected_rows($this->conn);
	}

	/**
	 *
	 * @param <type> $tablename
	 * @param sp_db_row $row
	 * @return <type>
	 */
	public function Insert($tablename, DbRow &$row) {
		$field_list = "";
		$value_list = "";
		$count = 0;
		foreach($row as $key => $field) {
			if (get_magic_quotes_gpc()) $value = stripslashes($value);
			$value = mysql_real_escape_string($value);
			if ($count > 0) {
				$field_list .= ", `$key`";
				$value_list .= ", '$field'";
			} else {
				$field_list .= "`$key`";
				$value_list .= "'$field'";
			}
			$count++;
		}

		$sql = "INSERT INTO `$tablename` ($field_list) VALUES ($value_list)";
		return $this->Query($sql);
		//echo "$sql<br />";
	}

	/**
	 *
	 * @param <type> $tablename
	 * @param sp_db_row $row
	 * @return <type>
	 */
	public function Replace($tablename, DbRow &$row) {
		$field_list = "";
		$value_list = "";
		$count = 0;
		foreach($row as $key => $field) {
			if (get_magic_quotes_gpc()) $value = stripslashes($value);
			$value = mysql_real_escape_string($value);
			if ($count > 0) {
				$field_list .= ", `$key`";
				$value_list .= ", '$field'";
			} else {
				$field_list .= "`$key`";
				$value_list .= "'$field'";
			}
			$count++;
		}

		$sql = "REPLACE INTO `$tablename` ($field_list) VALUES ($value_list)";
		return $this->Query($sql);
		//echo "$sql<br />";
	}

	/**
	 *
	 * @param <type> $tablename
	 * @param <type> $id
	 * @param sp_db_row $row
	 * @param sp_db_row $where
	 * @return <type>
	 */
	public function Update($tablename, $id, DbRow $row, DbRow $where=null) {
		$set_list = "";
		$count = 0;
		foreach($row as $key => $value) {
			if (get_magic_quotes_gpc()) $value = stripslashes($value);
			$value = mysql_escape_string($value);
			if ($count > 0) {
				$set_list .= ", `$key`='$value'";
			} else {
				$set_list .= "`$key`='$value'";
			}
			$count++;
		}
		if ($id instanceof sp_db_row)
			$where = $id;

		if ($where != null) {
			// Adtunk meg teljes Where t�bl�t!
			$w_count = 0;
			$where_list = "";
			foreach($where as $key => $value) {
				$value = mysql_real_escape_string($value);
				if ($w_count > 0) {
					$where_list .= ", `$key`='$value'";
				} else {
					$where_list .= "`$key`='$value'";
				}
				$w_count++;
			}

			$sql = "UPDATE `$tablename` SET $set_list WHERE $where_list";
		} else {
			// Csak ID-t adtunk meg
			$sql = "UPDATE `$tablename` SET $set_list WHERE id='$id'";
		}
		return $this->Query($sql);
		//echo "$sql<br />";
	}

	/**
	 *
	 * @param <type> $tablename
	 * @param <type> $row
	 * @param <type> $or_operator
	 * @return <type>
	 */
	public function Delete($tablename, $row, $or_operator = NULL) {
		$set_list = "";
		$count = 0;
		$op = "AND";
		if ($or_operator == true) $op = "OR";
		if ($row instanceof DbRow === false)
			$row = new DbRow(array("id" => $row));

		foreach($row as $key => $value) {
			$value = mysql_escape_string($value);
			if ($count > 0) {
				$set_list .= " $op `$key`='$value'";
			} else {
				$set_list .= "`$key`='$value'";
			}
			$count++;
		}
		$sql = "DELETE FROM `$tablename` WHERE $set_list";
		return $this->Query($sql);
		//echo "$sql<br />";
	}


	/**
	 * Retreive info about the server
	 * @return string info
	 */
	public function ServerInfo() {
		return mysql_get_server_info($this->conn);
	}

	/**
	 * Get details about the current system status
	 * @return array details
	 */
	public function Status() {
		return explode('  ', mysql_stat($this->conn));
	}

	/**
	 * Escape a string to make it safe for mysql
	 * @param <type> $string
	 * @return <type>
	 */
	public function Escape($string) {
		return mysql_real_escape_string($string, $this->conn);
	}
}
?>
