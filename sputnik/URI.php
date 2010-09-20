<?php

/**
 * URI Helper class
 * @author Daniel Fekete
 * @copyright 2010(c) VOOV Ltd.
 */
class URIHelper {
	var $class_name = "";
	var $class_path = array();
	var $uri_array = array();
	var $path_length = 0;
	var $dir_path = "";

	function  __construct() {
		global $uri_mappings;
		$uri_array = explode("/", $_SERVER["REQUEST_URI"]);
		$uri_filtered = array_filter($uri_array, array($this, "remove_index"));
		$uri = implode("/", $uri_filtered);

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

		$this->uri_array = explode("/", $uri);
		$this->get_class_path($this->uri_array);
		$this->path_length = count($this->class_path);
		$this->class_name = $this->uri_array[$this->path_length];

		$this->dir_path = implode("/", $this->class_path);
	}

	private function remove_index($var) {
		// strip out leading php controller files if present
		if (strpos($var, ".php") !== false) return false;
		else {
			// do a quick standard URI check
			// Framework 3 is VERY restrictive about URIs
			if (preg_match('/^[a-zA-Z0-9_-][a-zA-Z0-9_-]*$/', $var)) {
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

class URI {
	static function RedirectToReferer() {
		$referer = $_SERVER["HTTP_REFERER"];
		if (headers_sent() == false)
			header("Location: $referer");
	}

	static function Redirect($uri) {
		if (headers_sent() == false)
			header("Location: " . URI::MakeURL($uri));
	}

	static function MakeURL($uri) {
		$host = $_SERVER["HTTP_HOST"];
		$ret = Hooks::GetInstance()->CallHookAtPoint("pre_makeurl", array($host, $uri));
		if ($ret != null)
			return $ret;
		else
			return "http://$host/$uri";
	}
}

?>
