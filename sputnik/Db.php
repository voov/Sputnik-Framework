<?php


require_once "DbAdapter.php";
require_once "DbResult.php";
require_once "DbRow.php";
require_once 'QueryBuilder.php';
require_once "config/config.php";

class Db {

	private $db_adapter = null;
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
	private static $instance = false;


	private $disable = false;
	private $is_prepared = false;
	private $prep_vars = array();

	private $last_query = "";

	private $count_query = 0;

	/**
	 * Constructor
	 * @param string server
	 * @param string username
	 * @param string password
	 * @param string db name
	 */
	public function __construct($host, $user, $pass, $db=false, $db_adapter=null) {
		global $config;
		if(!$db_adapter)
			$this->db_adapter = DbAdapter::factory($config["db_adapter"]);
		else
			$this->db_adapter = $db_adapter;
		$this->conn = $this->db_adapter->Connect($host, $user, $pass);
		if ($this->conn && $db) $this->db_adapter->SwitchDb($db);
	}

	public function  __set($name,  $value) {
		$this->is_prepared = true;
		$this->prep_vars[$name] = $value;
	}


	public function __destruct() {
		$this->db_adapter->Disconnect();
	}

	public function Disable($set_disable=true) {
		$this->disable = $set_disable;
	}

	/**
	 * @static
	 * @return null|object
	 */
	public static function GetInstance() {
		global $config;
		if (self::$instance === false) {
				self::$instance = new Db($config["db_connect"], $config["db_username"], $config["db_password"], $config["db_dbname"]);
		}
		return self::$instance;
	}


	/**
	 * Change databases
	 * @param string database
	 */
	public function SelectDatabase($db) {
		$this->db_adapter->SwitchDb($db);
		$this->db = $db;
	}

	/**
	 * Switch To Utf-8 encoding
	 * @param string database
	 */
	public function Switch_Utf8() {
		$this->db_adapter->Query("SET CHARACTER SET utf8");
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
	 *
	 * @param <type> $groups
	 * @return <type>
	 */
	private function ReplaceCallback($groups) {
		//print_r($groups);
		$val = $this->prep_vars[$groups[1]];
		if (get_magic_quotes_gpc()) $val = stripslashes($val);
		$val = $this->db_adapter->EscapeString($val);
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
	 * Run a query against the database and return a DbResult object
	 * @return object DbResult
	 */
	public function Query($query, $send_in_utf8 = false) {
		global $config;
		if($query instanceof QueryBuilder)
			$query = $query->Render();
		
		$uselimit = false;

		
		$tokens = explode(' ', strtolower($query));
		$query_can_cache = ($tokens[0] == "select" && in_array("rand", $tokens)) ? true : false; // only cache select queries
		
		if (in_array("limit", $tokens) && $tokens[0] == "select") {
			$query = str_replace("select", "select sql_calc_found_rows", strtolower($query));
			$uselimit = true;
		}

		if ($send_in_utf8 == true) {
			$this->Switch_Utf8();
		}
		
		if ($this->is_prepared) $query = $this->PrepareQuery($query);
		$this->last_query = $query;

		// check query cache
		if($config["db_is_cached"] && $query_can_cache) {
			$cache = Cache::Factory($config["db_cache_adapter"]);
			if(($val = $cache->Get(md5($query))) !== false) {
				return $val; // return cached DbResult
			}
		}

		$result = $this->db_adapter->Query($query, $this->conn) or
			   trigger_error("SQL Hiba: " . $this->db_adapter->GetError() . "<br /><b>" . $query . "</b>", E_USER_ERROR);

		$this->count_query++;
		if ($this->is_prepared) {
			$this->prep_vars = array();
			$this->is_prepared = false;
		}
		if (count($tokens) > 0) {
			if (!in_array(trim($tokens[0]), array('update', 'delete', 'insert', 'replace'))) {
				//$insert = true;
				$result = new DbResult($result, $this->conn, $insert, $uselimit);
				if($config["db_is_cached"] && $query_can_cache) {
					// $cache is already defined
					$cache->Set(md5($query), $result);
				}
				return $result;
			} elseif(trim($tokens[0]) == "insert")
				if($config["db_is_cached"] && $query_can_cache) {
					$cache->Clean();
				}
				return $this->db_adapter->GetInsertedId();
		}
		if($config["db_is_cached"] && $query_can_cache) {
			$cache->Clean();
		}
		return $this->db_adapter->GetAffectedRows();
	}


	/**
	 *
	 */
	public function DumpLastQuery() {
		echo $this->last_query;
	}

	public function GetQueryCount() {
		return $this->count_query;
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
			$value = $this->db_adapter->EscapeString($value);
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
			$value = $this->db_adapter->EscapeString($value);
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
		if ($id instanceof DbRow)
			$where = $id;

		if ($where != null) {

			$w_count = 0;
			$where_list = "";
			foreach($where as $key => $value) {
				$value = $this->db_adapter->EscapeString($value);
				if ($w_count > 0) {
					$where_list .= "AND `$key`='$value'";
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
			$value = $this->db_adapter->EscapeString($value);
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
		return $this->db_adapter->Info();
	}

	/**
	 * Escape a string to make it safe for mysql
	 * @param <type> $string
	 * @return <type>
	 */
	public function Escape($string) {
		return $this->db_adapter->EscapeString($string, $this->conn);
	}
}
?>
