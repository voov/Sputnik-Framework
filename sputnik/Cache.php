<?php

include_once "ICacheAdapter.php";
 
class Cache implements ICacheAdapter {

	public function __construct() {
		// Nothing to do
	}

	public static function Factory($adapter) {
		$adapter_file = "cache/" . $adapter . ".php";

		if(!is_file($adapter_file)) {
			trigger_error("Could not find '$adapter' cache adapter!");
		}
		
		require_once $adapter_file;

		return new $adapter();
	}

	public function Clean() {
		return false;
	}

	public function Remove($key) {
		return false;
	}

	public function Set($key, $value) {
		return false;
	}

	public function Get($key) {
		return false;
	}
}
