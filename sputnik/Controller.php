<?php
/**
 * Sputnik Controller
 * @version 3.0
 * @author Daniel Fekete
 * @copyright VOOV Ltd.
 */

require_once("config/config.php");
require_once("config/localization.php");
require_once("config/uri_mappings.php");
require_once("config/autoload.php");

require_once ("Hooks.php");
require_once("URI.php");
require_once("Helper.php");
require_once("Renderable.php");
require_once("Sessions.php");
require_once("Request.php");
require_once("Db.php");
require_once("PluginLoader.php");
require_once("Localization.php");
require_once("Form.php");

if($config["enable_imagecache"] == true) {
	require_once 'ImageCache.php';
}


function debug_string_backtrace() {
   ob_start();
   debug_print_backtrace();
   $trace = ob_get_contents();
   ob_end_clean();

   // Remove first item from backtrace as it's this function which
   // is redundant.
   $trace = preg_replace ('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

   // Renumber backtrace items.
   $trace = preg_replace ('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

   return $trace;
}


/**
 *
 */
abstract class Controller {

	public $view = null;
	public $db = null;
	public $session = null;
	public $uri_helper;
    public $form = null;

	function __construct() {
		
		$this->view = new Renderable();
		$this->db = Db::getInstance();
		$this->session = Sessions::getInstance();
		$this->uri_helper = new URIHelper();
	}

	public function GetURIPart($index) {
		return $this->uri_helper->uri_array[$this->uri_helper->path_length+$index];
	}


	public function GetRequest() {
		return Request::getInstance();
	}


	/**
	 * Loads an external plugin
	 * @return plugin object
	 * @param $name the name of the plugin to load
	 * @param $args optional arguments
	 */
	public function LoadPlugin($name, $args=array()) {
		return PluginLoader::LoadPlugin($name, $this, $args);
	}


	/**
	 * Returns the session singelton object
	 * @return session object
	 */
	public function GetSession() {
		return Sessions::getInstance();
	}



	/**
	 * Forward
	 *
	 *
	 * @return
	 * @param $method which class to call
	 * @param $action which method to call from class, defaults to 'main'
	 */
	public function Forward($method = "", $action = "main") {
		
        global $config;
		$parameters_index = 1; //calc in class
		$action_buffer = $this->GetURIPart($parameters_index);
		if (method_exists($this, $action_buffer)) {
			$action = $action_buffer;
			$parameters_index+=1;
		}

		if (!method_exists($this, $action)) {
			// error 404
			trigger_error("There is no '$action' in '$this'!", E_USER_ERROR);
		}

		$classReflect = new ReflectionClass($this);
		$classActionMethod = $classReflect->getMethod($action);

		if ($classActionMethod->isPublic() != true)
			trigger_error("'$action' is not public!", E_USER_ERROR);
		
		$parameters = array();
		if ($classActionMethod->getNumberOfParameters() > 0) {
			$actionParameters = $classActionMethod->getParameters();
			$paramCounter = 0;
			
			foreach($actionParameters as $param) {
				$val = $this->GetURIPart($paramCounter+$parameters_index);
				$parameters[] = $val;
				$paramCounter++;
			}
		}


		// Call the _autorun method if it exists
		if (method_exists($this, "_autorun"))
			$this->_autorun();

        if(method_exists($this, "_authenticate")) {
            // authentikáció szükséges
            $ret = $this->_authenticate($_POST[$config["form_username"]], $_POST[$config["form_password"]], $action);
            if($ret == false) {
                //$this->Forward($method, $action); // addig ne engedjük tovább, amíg nem jelentkezett be
                return;
            }
        }
		
		call_user_func_array(array($this, $action), $parameters);
	}

}


class Sputnik {

	static $instance = false;
	static $start_from = 0;
	private $uri_helper;
	static $controller = false;

	function  __construct() {
		$this->uri_helper = new URIHelper();
	}

	static function GetInstance() {
		if (Sputnik::$instance == false) {
			Sputnik::$instance = new self;
		}
		return Sputnik::$instance;
	}

	static function GetRunningInstance() {
		return Sputnik::$controller;
	}

	function Dispatch() {
		global $config;

		if($config["enable_imagecache"]) {

			if($this->uri_helper->uri_array[0] == $config["imagecache_controller"]) {
				$dir = implode("/", array_splice($this->uri_helper->uri_array, 1));
				$fname = basename($dir);
				$dir = preg_replace('/-[xr]([0-9]+)/', '', $dir);
				//if(!is_file($dir)) return;
				// Get picture size and function
				preg_match("/-([xr])([0-9]+)/", $fname, $matches);
				if($matches[1] == "r") {
					// resize
					$ic = new ImageCache();
					$ic->RenderImage($dir, $matches[2], $matches[2]);
				} elseif ($matches[1] == "x") {
					// crop
					$ic = new ImageCache();
					$ic->RenderImage($dir, $matches[2], $matches[2], true);
				}
				return; // End of imagecache, do not run as normal controller!
			}
		}

		// Get the filename
		$class_filename = $config["app_directory"] . $this->uri_helper->dir_path . "/" . strtolower($this->uri_helper->class_name) . ".php";
		if(!is_file($class_filename)) {
			die("Not class '$class_filename'"); // Error 404
			error_log("Not class '$class_filename'", 0);
		}



		require_once $class_filename;
		$controller = new $this->uri_helper->class_name();
        $controller->form = Form::GetInstance();
		Sputnik::$controller = $controller;
		global $autoload;
		foreach($autoload as $plugin_name) {
			$controller->LoadPlugin($plugin_name);
		}
		$controller->Forward();
	}
}


?>
