<?php
/**
 * Helper Functions
 * Auto-Load class
 */

class Helper {

	private static $instance = null;

	/**
	 * Return the current Helper instance
	 * @static
	 * @return null
	 */
	public static function GetInstance() {
		if(self::$instance == null) self::$instance = new Helper();
		return self::$instance;
	}

	/**
	 * Shortcut function for GetInstance
	 * @static
	 * @return void
	 */
	public static function GI() {
		return self::GetInstance();
	}

	/**
	 * Loads a helper function
	 * @param  $method The name of the method to load
	 * @param  $args The helper's arguments
	 * @return bool|mixed
	 */
	public function LoadFunction($method, $args) {
		// First look in Sputnik own helper lib
		$include = "helpers/$method.php";
		if(!is_file($include)) $include = "../" . $include; // step back one dir
		if (is_file($include)) {
			include_once $include;
			return call_user_func_array($method, $args);
		}
		return false;
	}

	/**
	 * Static magic call to helper function
	 * Works in PHP 5.3 and above only!
	 * @param <type> $method
	 * @param <type> $args
	 * @return <type>
	 */
	public static function __callStatic($method, $args) {
		return self::GetInstance()->LoadFunction($method, $args);
	}

	/**
	 * Magic call to helper function
	 */
	public function __call($method, $args) {
		return $this->LoadFunction($method, $args);
	}
}
?>
