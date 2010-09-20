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
require_once ("Hooks.php");
require_once("URI.php");
require_once("Helper.php");
require_once("Template.php");
require_once("Sessions.php");
require_once("Request.php");
require_once("Db.php");
require_once("PluginLoader.php");
require_once("Localization.php");


/**
 *
 */
abstract class Controller {

	public $view = null;
	public $db = null;
	public $session = null;
	public $uri_helper;


	function __construct() {
		$this->view = Template::getInstance();
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
		$action_buffer = $this->GetURIPart($parameters_index);
		if (method_exists($controller, $action_buffer)) {
			$action = $action_buffer;
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
			$paramCounter = 0;
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


class Sputnik extends Controller {

	static $instance = false;
	static $start_from = 0;

	function GetInstance() {
		if (!Sputnik::$instance) {
			Sputnik::$instance = new self();
		}
		return Sputnik::$instance;
	}

	function Dispatch() {
		$this->Forward();
	}
}


?>
