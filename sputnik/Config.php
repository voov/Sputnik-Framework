<?php

 
class Config {

	private $m_config = array();

	function __construct($basepath = "") {

		include_once "config/config.php";
		array_merge($this->m_config, $config);
		if(is_file($basepath . "/config/config.php") && $basepath != "") {
			// we load from module
			$config = array(); // clear the $config global
			include_once $basepath . "/config/config.php";
			array_merge($this->m_config, $config);
		}
	}

	public function Get($config_key) {
		return $this->m_config[$config_key];
	}

	public function Set($config_key, $value) {
		$this->m_config[$config_key] = $value;
	}

}
