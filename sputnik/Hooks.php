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

	private function array_flatten($array, $preserve = FALSE, $r = array()){
        foreach($array as $key => $value){
            if (is_array($value)){
                foreach($value as $k => $v){
                    if (is_array($v)) { $tmp = $v; unset($value[$k]); }
                }
                if ($preserve) $r[$key] = $value;
                else $r[] = $value;
            }
          // this is correct
          $r = isset($tmp) ? $this->array_flatten($tmp, $preserve, $r) : $r;
        }
        // wrong spot:
        // $r = isset($tmp) ? array_flatten($tmp, $preserve, $r) : $r;
        return $r;
    }

	public function CallHookAtPoint($point, $params=array()) {
		//$last_result = array(null);
		if (!isset($this->hooks[$point])) return false;
		$func_param = array(null);
		foreach($params as $param) {
			$func_param[] = $param;
		}
		$param_count = count($func_param);
		foreach($this->hooks[$point] as $hook_point) {
			// we need to pass over results to the next hook function
			if(isset($last_result))
				$func_param[0] = $last_result;

			$hook_param_count = 0;
			foreach($hook_point[1] as $hook_params) {
				$func_param[$param_count+$hook_param_count] = $hook_params;
				$hook_param_count++;
			}
			// $hook_point[1]
			$last_result = call_user_func_array($hook_point[0], $func_param);
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
