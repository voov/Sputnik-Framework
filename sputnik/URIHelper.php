<?php

class URIHelper {
	var $class_name = "";
	var $class_path = array();
	var $uri_array = array();
	var $path_length = 0;
	var $dir_path = "";
	var $current_uri = "";

	function  __construct($basepath="") {

		global $config;
		if($basepath == "") $basepath = $_SERVER["REQUEST_URI"];

		$uri_filtered = array_filter(explode("/", $basepath), array($this, "find_namedparams"));
		$uri_filtered = array_filter($uri_filtered, array($this, "remove_index"));

		$uri = implode("/", $uri_filtered);
		$uri = $this->rewrites($uri);
		$this->current_uri = $uri;
		$this->uri_array = explode("/", $uri);
		

		$this->class_path = Helper::GI()->traverse_path($config["app_directory"], $this->uri_array);
		//$this->get_class_path($this->uri_array);
		$this->path_length = count($this->class_path);
		$this->class_name = $this->uri_array[$this->path_length];

		$this->dir_path = !empty($this->class_path) ? implode("/", $this->class_path) : "";
	}

	private function rewrites($uri) {
		global $uri_mappings;
		$ret = Hooks::GetInstance()->CallHookAtPoint("pre_urimap", array($uri_mappings));
		if($ret !== false) {
			$uri_mappings = $ret;
		}

		foreach($uri_mappings as $map_regex => $map_replace) {
			if (is_array($map_replace)) {
				// map replace is a user function!
				if (preg_match("/^" . str_replace("/", "\/", $map_regex) . "$/", $uri)) {
					$uri = call_user_func($map_replace, "/^" . str_replace("/", "\/", $map_regex) . "$/", $uri);
				}
			} else {
				$uri = preg_replace("/^" . str_replace("/", "\/", $map_regex) . "$/", $map_replace, $uri); // Replace the URI using the mappings
			}
		}
		return $uri;
	}

	private function find_namedparams($var) {
		global $config;
		$var = urldecode($var);
		
		if(strpos($var, $config["namedparam_char"]) !== false) {
			$param = explode($config["namedparam_char"], $var);
			URI::SetNamedParam($param[0], $param[1]);
			return false;
		}
		return true;
	}

	private function remove_index($var) {
		// strip out leading php controller files if present
		$var = urldecode($var);
		if (strpos($var, ".php") !== false) return false;
		else {
			// do a quick standard URI check
			// Framework 3 is VERY restrictive about URIs
			// To add URI support for named params - (\:[a-zA-Z0-9_|-]*)?
			if (preg_match('/^[a-zA-Z0-9_-]+(?:\.[a-z]{1,4})?$/', $var)) {
				return true;
			}
			return false;
		}
	}

	private function get_class_path($uri_array, $index=0) {
		global $config;
		$dir = $config["app_directory"] . implode("/", $this->class_path) . "/" .  $uri_array[$index];
		if (is_dir($dir)) {
			// Add to the class path
			$this->class_path[] = $uri_array[$index];
			// If the URI is longer
			if (count($uri_array) > $index) {
				$this->get_class_path($uri_array, $index+1); // Go one further
			}
		}
	}

}

?>