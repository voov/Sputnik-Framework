<?php


class Loader {

	private $return_data = array();
	private $post_data = array();
	private $uris = array();

	public function __construct($uri, $data) {
		if(!is_array($uri)) $uri = array($uri);
		$this->uris = $uri;
		$this->post_data = $data;
		return $this; // So we can chain factory with Execute()
	}

	public function LoadFromURI($data = array()) {
		$this->uris = array();
		$module = URI::GetNamedParam("module");
		if(is_array($module))
			$module_uri = implode("/", $module);
		else
			$module_uri = $module;
		$this->uris[] = $module_uri;
		$this->post_data = $data;
		return $this;
	}

	/**
	 * Saves output to return_data 
	 * @param  $uri
	 * @param  $data
	 * @return void
	 */
	private function SaveOutput($uri, $data) {
		// Save the data to the return table
		$key = array_search($uri, $this->uris);

		// First find out if we are still retrieving headers
		if(preg_match("/([\r\n][\r\n])\\1/", $data) == 1 && empty($this->return_data[$key])) {
			// we found our header
			$split = preg_split("/([\r\n][\r\n])\\1/", $data);
			$this->return_data[$key] = $split[1];
		} elseif(!empty($this->return_data[$key])) {
			$this->return_data[$key] .= $data;
		}
	}

		
	/**
	 * Loads an internal Controller and returns the full output
	 * @param  $uri
	 * @return void
	 */
	private function LoadInternal($uri) {
		global $config;
		// Load controller app with the following path
		// module_path -> app_path -> $uri
		foreach($uri as $uri_item) {
			if(empty($uri_item)) continue;
			
			// Go trough each URI, find the needed basepath, and load the controller
			// 0: module name
			// 1: class
			// 2-n: functions/parameters/named parameters
			$path_elements = explode("/", $uri_item);
			//$path_elements = array_filter($path_elements); // remove empty

			if(!ModuleHelper::IsEnabled($path_elements[0])) continue; // do not run not enabled module!
			
			// TODO: remove empty
			$basepath = implode("/", array_slice($path_elements, 1));
			$dirpath = Helper::GI()->traverse_path($config["module_directory"] . $path_elements[0] . "/" . $config["app_directory"], array_slice($path_elements, 1));
			$classname = $path_elements[count($dirpath)+1];
			$dirpath_str = !empty($dirpath) ? implode("/", $dirpath) : "";
			$classpath = $config["module_directory"] . $path_elements[0] . "/" . $config["app_directory"] . $dirpath_str . "/" . $classname . ".php";
			//$classpath = $config["module_directory"] . $path_elements[0] . "/" . $config["app_directory"] . $path_elements[1] . ".php";
			$viewpath = $config["module_directory"] . $path_elements[0] . "/";

			
			// Get the controller's output

			$post_buffer = $_POST;
			$_POST = array_merge($_POST, $this->post_data);
            
			$cur_domain = Localization::GetInstance()->GetCurrentDomain();
			Localization::GetInstance()->SetDomain($path_elements[0]);
			
			ob_start(); // start output buffering
				require_once $classpath;
				$objName = $path_elements[0] . "_" . $classname;

				$controller = new $objName($basepath, $viewpath);
				$controller->Forward();
				$key = array_search($uri_item, $this->uris);
				$this->return_data[$key] = ob_get_contents();
			ob_end_clean();
			
			Localization::GetInstance()->SetDomain($cur_domain);
			// switch back to the original POST array
			$_POST = $post_buffer;
		}
	}

	/**
	 * Loads an external Controller (or whatever) and returns the full output
	 * @param  $uri
	 * @return void
	 */
	private function LoadExternal($uri) {
		$timeout = 15;
		$sockets = array();

		// Open all sockets as stream
		foreach($uri as $uri_item) {
			$uri_info = parse_url($uri_item);
			$path = $uri_info["path"];
			$host = $uri_info["host"];
			if ($path == "") $path = "/"; // fix the bug, when there is no trailing slash
			$s = stream_socket_client("$host:80", $errno, $errstr, $timeout, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
			if($s) {
				$sockets[$uri_item] = $s;
				// make it post if there are data available
				if(count($this->post_data) > 0) {
					$data = http_build_query($this->post_data);
					$length = strlen($data);
					fwrite($s, "POST $path HTTP/1.1\r\nHost: $host\r\nConnection: close\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: $length\r\n\r\n$data");
				} else {
					fwrite($s, "GET $path HTTP/1.1\r\nHost: $host\r\nConnection: close\r\n\r\n");
				}
			}
		}
		// Now wait for all sockets to finish
		while(count($sockets)) {
			// while there are open sockets
			$read_buffer = $sockets;
			stream_select($read_buffer, $w = null, $e = null, $timeout);

			if(count($read_buffer)) {
				// if there are readable sockets
				foreach($read_buffer as $readable) {
					// find URI
					$read_uri = array_search($readable, $sockets);
					$data = fread($readable, 8192); // read data
					if(strlen($data) == 0 || feof($readable)) {
						fclose($readable);
						unset($sockets[$read_uri]);
					} else {
						$this->SaveOutput($read_uri, $data);
					}
				}
				
			} else {
				// All streams timed out!
				// TODO
				break;
			}
		}
	}

	/**
	 * Execute the requests, load the controllers, return the data
	 * @return void
	 */
	public function Execute($cache=false) {
		$external_uris = array();
		$internal_uris = array();
		
		// Go trough all URIs and separate external and internal
		foreach($this->uris as $uri) {
			if($cache != false && ($val = $cache->Get($uri)) !== false) {
				// use the cached version
				$this->return_data[$uri] = $val;
				continue;
			}
			if(strpos($uri, "http://") !== false) $external_uris[] = $uri;
			else $internal_uris[] = $uri;
		}

		// Load all resources first internal then external
		$this->LoadInternal($internal_uris);
		$this->LoadExternal($external_uris);

		foreach($this->return_data as $uri => $data) {
			if($cache)
				$cache->Set($uri, $data);
		}
		
		// If we only have one return, just give it back to the controller
		// otherwise send as an array
		if(count($this->return_data) <= 1)
			return $this->return_data[0];
		return $this->return_data;
		//return $this->return_data[0];
	}

	public static function Factory($uri="", $data=array()) {

		return new Loader($uri, $data);
	}
}
