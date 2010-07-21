<?php
/**
 * Helper Functions
 * Auto-Load class
 */

class Helper {
	/**
	 * Static magic call to helper function
	 * Works in PHP 5.3 and above only!
	 * @param <type> $method
	 * @param <type> $args
	 * @return <type>
	 */
	public static function __callStatic($method, $args) {
		if (!function_exists($method)) {
			include "helpers/$method.php";
			return call_user_func_array($method, $args); //$method($args);
		}
		return false;
	}

	/**
	 * Magic call to helper function
	 */
	public function __call($method, $args) {
		if (!function_exists($method)) {
			include "helpers/$method.php";
			return call_user_func_array($method, $args);
		}
		return false;
	}
}
?>
