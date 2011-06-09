<?php


class Debugger {

	private $logs = array();

	function log($var, $type = "log") {
		if (is_object($var) || is_array($var))
			$var = json_encode($var);
		else
			$var = "\"" .  addslashes($var) . "\"";

		$this->logs[$type] = $var;
	}

	function __destruct() {
		echo "<script type=\"text/javascript\">";
		echo "if(window.console) {";

		foreach($this->logs as $type=>$log) {
			echo "console.$type($log);";
		}

		echo "} </script>";
	}
}
