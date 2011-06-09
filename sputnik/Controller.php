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

/*
require_once("Config.php");
require_once ("Hooks.php");
require_once("URI.php");
require_once("Helper.php");
require_once("Renderable.php");
require_once("Sessions.php");
require_once("Db.php");
require_once("PluginLoader.php");
require_once("Localization.php");
require_once("Form.php");
require_once("Loader.php");
require_once("Auth.php");
*/

if($config["enable_imagecache"] == true) {
	require_once 'ImageCache.php';
}

function __autoload($className) {
	require_once($className . ".php");
}

/**
 *
 */
abstract class Controller {

	public $view = null;
	//public $db = null;
	public $session = null;
	public $uri_helper;
    public $form = null;
	
	//public $config = null;

	private $output_contents;

	function __construct($basepath="", $viewpath="") {

		// Get singletons
		//$this->db = Db::GetInstance();
		$this->session = Sessions::GetInstance();
	
		// Get new instances
		$this->view = new Renderable($viewpath);
		$this->uri_helper = new URIHelper($basepath);
		
		//$this->config = new Config($basepath);
		$this->form = Form::Factory();
	}

	public function StartBuffering() {
		ob_start();
	}

	public function EndBuffering() {
		$this->output_contents = ob_get_contents();
		ob_end_clean();
	}

	public function OutputBuffer() {
		echo $this->output_contents;
	}

	public function __get($var) {
		if(isset($this->$var)) return $this->$var; 	
		switch($var) {
			case "db": $this->$var = Db::GetInstance(); break;
			case "session": $this->$var = Sessions::GetInstance(); break;
			//case "form": $this->$var = Form::Factory(); break;
		}
		return $this->$var;
	}



	/**
	 * Get's a part of the URI
	 * @param  $index
	 * @return array
	 */
	public function GetURIPart($index) {
		//var_dump($this->uri_helper->uri_array);

		if(count($this->uri_helper->uri_array) < ($this->uri_helper->path_length+$index)-1)
			return false;
		return $this->uri_helper->uri_array[$this->uri_helper->path_length+$index];
	}


	/**
	 * @deprecated Since version 3.0
	 * @return bool
	 */
	public function GetRequest() {
		return $this->form;
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
		if ($action_buffer && method_exists($this, $action_buffer)) {
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
                // addig ne engedjük tovább, amíg nem jelentkezett be
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
	public $modules = null;

	

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

	/**
	 * Module loader core
	 * @return void
	 */
	private function LoadModules() {
		global $config;
		$dir = $config["module_directory"];
		if(is_dir($dir)) {
			// if there is a module directory present
			$modules = array_slice(scandir($config["module_directory"]), 2);
			foreach($modules as $module) {

				$module_file = $dir . $module . "/" . ucfirst($module) . "Module.php";

				if(is_file($module_file)) {
					require_once($module_file);
					$module_class_name = ucfirst($module) . "Module";
					$this->modules[$module_class_name] = new $module_class_name();
					$this->modules[$module_class_name]->initialize();
				}
			}
		}
	}

	private function RenderImageCache() {
		global $config;
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
			exit;
		}
	}

	function Dispatch() {
		global $config;

		if($config["enable_imagecache"]) {
			$this->RenderImageCache();
		}
		
		
		// Load the modules
		$this->LoadModules();

	
		// Get the filename
		$class_filename = $config["app_directory"] . $this->uri_helper->dir_path . "/" . strtolower($this->uri_helper->class_name) . ".php";
		if(!is_file($class_filename)) {
			error_log("Not class '$class_filename'", 0);
			//die("Not class '$class_filename'"); // Error 404
			Helper::GI()->error_404();
		}
		error_log(microtime() . " --> " . var_export($_POST, true));
		error_log(Helper::GI()->debug_string_backtrace());
		
		require_once $class_filename;
		$controller = new $this->uri_helper->class_name();
		Sputnik::$controller = $controller;
		global $autoload;
		foreach($autoload as $plugin_name) {
			$controller->LoadPlugin($plugin_name);
		}

		
		$controller->StartBuffering();
		    $controller->Forward();
		$controller->EndBuffering();


		if(REST::GetInstance()->HasResponse())
		    REST::GetInstance()->Execute();
		else
		    $controller->OutputBuffer();
	}
}


?>
