<?php
/**
 * Provide hooking capabilites to Sputnik
 * Hook points:
 * -
 * @author Daniel Fekete
 */
class Hooks {
	
	static $instance = null;
	private $hooks = array();

	public function RegisterFunction($point, $callback, $params=array()) {
		$hook_hash = md5(uniqid());
		$this->hooks[$point][$hook_hash] = array($callback, $params);
		return $hook_hash;
	}

	public function UnregisterFunction($point, $hook_hash) {
		unset($this->hooks[$point][$hook_hash]);
	}

	public function CallHookAtPoint($point, $params=array()) {
		$last_result = array(null);
		if (!isset($this->hooks[$point])) return false;
		foreach($this->hooks[$point] as $hook_point) {
			// we need to pass over results to the next hook function
			$hook_params = array_merge((array)$last_result, (array)$params, (array)$hook_point[1]);
			
			$last_result = call_user_func_array($hook_point[0], $hook_params);
		}
		if (count($last_result) == 0) return false;
		return $last_result;
	}

	public static function GetInstance() {
		if(self::$instance === null) self::$instance = new Hooks();
		return self::$instance;
	}
}
?>
