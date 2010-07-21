<?php
/**
 * Sputnik Controller
 * @version 3.0
 * @author Daniel Fekete
 * @copyright VOOV Ltd.
 */

require_once("Helper.php");
require_once("Template.php");
require_once("Sessions.php");
require_once("Request.php");
require_once("Db.php");
require_once("PluginLoader.php");
require_once("config/uri_mappings.php");


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
			$uri = preg_replace("/^" . str_replace("/", "\/", $map_regex) . "$/", $map_replace, $uri); // Replace the URI using the mappings
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

/**
 * 
 */
abstract class Controller {

	public $view = null;
	public $db = null;
	public $uri_helper;
	

	function __construct() {
		$this->view = Template::getInstance();
		$this->db = Db::getInstance();

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
	 * protected f�ggv�ny, csak �r�k�lt oszt�lyokban haszn�lhat�, tov�bb�tja a k�r�seket
	 * a megfelel� class f�jlba
	 * @return
	 * @param $method Object melyik oszt�lyt kell megh�vnia
	 * @param $action Object[optional] melyik f�ggv�nyt h�vja meg az oszt�lyon bel�l
	 */
	protected function Forward($method = "", $action = "main") {
		global $config;
		// Get the filename
		$class_filename = $config["app_directory"] . $this->uri_helper->dir_path . "/" . strtolower($this->uri_helper->class_name) . ".php";
		if(!is_file($class_filename)) {
			die("Not class '$method'"); // Error 404
			error_log("Not class '$method'", 0);
		}

		require_once $class_filename;
		$controller = new $this->uri_helper->class_name();

		$parameters_index = 1; //calc in class

		if (method_exists($controller, $this->uri_helper->uri_array[$parameters_index])) {
			$action = $this->uri_helper->uri_array[$parameters_index];
			$parameters_index+=1;
		}

		if (!method_exists($controller, $action))
			trigger_error("There is no '$action' in '$controller'!", E_USER_ERROR);
		
		$classReflect = new ReflectionClass($controller);
		$classActionMethod = $classReflect->getMethod($action);

		if ($classActionMethod->isPublic() != true)
			trigger_error("'$action' is not public!", E_USER_ERROR);

		if ($classActionMethod->getNumberOfParameters() > 0) {
			$actionParameters = $classActionMethod->getParameters();
			$paramCounter = -1;
			$parameters = array();
			foreach($actionParameters as $param) {
				$val = $this->GetURIPart($paramCounter+$parameters_index);
				$parameters[] = $val;
				$paramCounter++;
			}
		}

		

		// Call the _autorun method if it exists
		if (method_exists($controller, "_autorun"))
			$controller->_autorun();
		
		call_user_func_array(array($controller, $action), $parameters);
	}

}


class FrontController extends Controller {

	static $instance = false;
	static $start_from = 0;

	function GetInstance() {
		if (!FrontController::$instance) {
			FrontController::$instance = new self();
		}
		return FrontController::$instance;
	}

	function Dispatch() {
		$this->Forward();
	}
}


?>
