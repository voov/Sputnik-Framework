<?php

 
class DbAdapter {
	public static function Factory($adapter) {
		$adapter_file = "sputnik/db/" . $adapter . ".php";
		
		if(!is_file($adapter_file)) {
			trigger_error("Could not find '$adapter' database adapter in '$adapter_file'!");
		}
		require_once $adapter_file;
		return new $adapter();
	}
}
