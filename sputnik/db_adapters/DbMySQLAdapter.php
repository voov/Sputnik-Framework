<?php

require_once "../IDbAdapter.php";
 
class DbMySQLAdapter implements IDbAdapter {


	public function Info() {
		// TODO: Implement Info() method.
	}

	public function EscapeString($string) {
		// TODO: Implement EscapeString() method.
		return mysql_real_escape_string($string);
	}

	public function Query($query_string) {
		// TODO: Implement Query() method.
	}

	public function SwitchDb($db) {
		// TODO: Implement SwitchDb() method.
	}

	public function Disconnect() {
		// TODO: Implement Disconnect() method.
	}

	public function Connect($server, $username, $password) {
		return mysql_connect($server, $username, $password);
	}
}
