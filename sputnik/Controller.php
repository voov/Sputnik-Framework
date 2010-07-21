<?php
/**
 * Sputnik Controller
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 *
 * History:
 * 2.02 - Controller meg tud h�vni param�tereket is
 * 2.05 - minden Controller oszt�ly kap egy _autorun f�ggv�nyt, ami mindig lefut a main() el�tt
 */

require_once("Helper.php");
require_once("Template.php");
require_once("Sessions.php");
require_once("Db.php");
require_once("PluginLoader.php");


/*	function __autoload($className) {

		//exit;
	}*/

/**
 * Controller
 * @classDescription Minden app-t ebb�l az oszt�lyb�l kell �r�k�ltetni
 * @return
 */
abstract class Controller {

	private $start_from = 0;
	private $o_requests = null;
	public $view = null;
	public $db = null;


	function __construct() {
		$this->view = Template::getInstance();
		$this->db = Db::getInstance();
	}

	/**
	 * GetRequest
	 * Visszaad egy objektumot, amiben a request-ek vannak t�rolva
	 * P�lda: $this->GetRequest()->edit_id
	 * @return session object
	 */
	public function GetRequest() {
		if ($this->o_requests != null) return $this->o_requests;
		else {
			$o = (object)null;

			// N�zz�k el�sz�r a rewrite-olt URL-eket
			$req = $_SERVER["REQUEST_URI"];
			$req_parts = explode("/", $req);
			print_r($req_parts);

			for($i = FrontController::$start_from-1; $i<count($req_parts); $i+=2)
				if (!empty($req_parts[$i])) $o->{$req_parts[$i]} = $req_parts[$i+1];

			foreach($_REQUEST as $req => $req_val) {
				$o->{$req} = $req_val;
			}
			$this->o_requests = $o;
			return ($o);
		}
	}

	/**
	 * GetRequestNumber
	 * Visszaadja az �sszes GET request sz�m�t, amit rewrite-al adtunk meg, az index.php-t soha nem n�zi!
	 * @return az �sszes GET request sz�m�t, amit rewrite-al adtunk meg
	 */
	public function GetNumberOfRequests() {

		// N�zz�k el�sz�r a rewrite-olt URL-eket
		$req = $_SERVER["REQUEST_URI"];
		$req_parts = explode("/", $req);
		$counter = 0;
		foreach($req_parts as $req_part) {
			if (strpos($req_part, ".php") === true) continue;
			$counter++;
		}

		return $counter;
	}

	/**
	 * GetRequestAt
	 * Visszaad egy request string-et a megadott indexen
	 * @return request string a megadott indexen
	 * @param object $index a request indexe, az index.php-t soha nem n�zi!
	 */
	public function GetRequestAt($index) {

		// N�zz�k el�sz�r a rewrite-olt URL-eket
		$req = $_SERVER["REQUEST_URI"];
		$req_parts = explode("/", $req);
		if (strpos($req_parts[$index], ".php") === true && $this->GetNumberOfRequests() > 0) return $req_parts[$index+1];
		elseif(strpos($req_parts[$index], ".php") === true && $this->GetNumberOfRequests() == 0) return null;
		return $req_parts[$index];
	}

	/**
	 *
	 * @return
	 * @param $name Object
	 */
	public function LoadPlugin($name, $args=array()) {
		return PluginLoader::LoadPlugin($name, $this, $args);
	}


	/**
	 * GetSession
	 * Visszaad egy objektumot, amiben a session-�k vannak t�rolva
	 * P�lda: $this->GetSession()->username visszaadja a username session-t
	 * P�lda: $this->GetSession()->username = "voov" be�ll�tja a username sessiont-t
	 * @return session object
	 */
	public function GetSession() {
		return Sessions::getInstance();
	}

	
	/**
	 * GetClassPath
	 * Bels� f�ggv�ny, visszaadja a controllert �ltal k�rt oszt�lyt, a sz�ks�ges k�nyvt�rral
	 * @return
	 */
	private function GetClassPath() {
		$req = $_SERVER["REQUEST_URI"];
		$req_parts = explode("/", $req);
		$o = (object)null;

		$dir_path = array();
		// N�zz�k meg az �sszes request sort
		foreach($req_parts as $r_part) {
			// ha van benne egy .php, akkor valami.php lesz, l�phet�nk tov�bb
			FrontController::$start_from++;
			if (strpos($r_part, ".php") !== false) continue;

			//TODO: m�ly k�nyvt�rkeres�s, mert �gy nem j�!
			if (is_dir("apps/" . $r_part)) {
				// k�nyvt�r
				$dir_path[] = $r_part;
				continue;
			}

			if (!isset($o->className))
				$o->className = $r_part;
			else {
				$o->isMethod = $r_part;
				break;
			}

		}

		$o->dirPath = implode("/", $dir_path);
		return $o;
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

		// tisztítsuk meg a $method-ot
		$method = preg_replace("/\W/si", "", $method);

		$cp = $this->GetClassPath();
		if (empty($method)) {
			$method = ucfirst($cp->className);
		}

		if (!isset($config["app_directory"])) $config["app_directory"] = "apps/";

		if(!is_file($config["app_directory"] . $cp->dirPath . "/" . strtolower($method) . ".php")) {
			die("Not class '$method'");
			error_log("Not class '$method'", 0);
		}

		require_once $config["app_directory"] . $cp->dirPath . "/" . strtolower($method) . ".php";
		$controller = new $method();

		if (method_exists($controller, $cp->isMethod)) {
			$action = $cp->isMethod;
			FrontController::$start_from++;
		}

		// N�zz�k meg param�tereket is
		$classReflect = new ReflectionClass($controller);

		$classActionMethod = $classReflect->getMethod($action);
		if ($classActionMethod->getNumberOfParameters() > 0) {
			$actionParameters = $classActionMethod->getParameters();
			$paramCounter = -1;
			$parameters = array();
			foreach($actionParameters as $param) {

				$val = $this->GetRequestAt(FrontController::$start_from+$paramCounter);
				$parameters[] = $val;

				$paramCounter++;
			}

			FrontController::$start_from += $paramCounter+1;
		}

		if (!method_exists($controller, $action))
			trigger_error("'$controller' oszt�lyban nem l�tezik '$action'!", E_USER_ERROR);

		// Ha l�tezik, h�vjuk meg az autorun f�ggv�nyt!
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
