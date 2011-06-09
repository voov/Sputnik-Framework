<?php

/**
 * URI Helper class
 * @author Daniel Fekete
 * @copyright 2010(c) VOOV Ltd.
 */



class URI {
	static $named_params = array();

	static function SetNamedParam($name, $value) {
		$name = htmlentities($name, ENT_COMPAT, "UTF-8");
		if(strpos($value, "|") !== false) {
			// The value supposed to be an array
			$value_array = explode("|", $value);
			//array_map("htmlentities", $value_array); // TODO: Find faster solution
			URI::$named_params[$name] = $value_array;
			
		} else {
			$value = htmlentities($value, ENT_COMPAT, "UTF-8");
            if(!empty($value) && $value != null && trim($value) != "")
			    URI::$named_params[$name] = $value;
		}
	}

	static function GetNamedParam($name, $default_value=false) {
		$name = html_entity_decode($name, ENT_COMPAT, "UTF-8");
		if(is_array(self::$named_params[$name])) $buffer = self::$named_params[$name];
		else $buffer = html_entity_decode(self::$named_params[$name], ENT_COMPAT, "UTF-8");
		if(empty($buffer)) return $default_value;
		return $buffer;
	}




	static function RedirectToReferer() {
		$referer = $_SERVER["HTTP_REFERER"];
		if (headers_sent() == false)
			header("Location: $referer");
	}

	static function Redirect($uri) {
		if (headers_sent() == false)
			header("Location: " . $uri);
	}

	static function MakeURL($uri, $extend_named=array(), $force_own_params=false) {
		global $config;
		$host = $_SERVER["HTTP_HOST"];
		$ret = Hooks::GetInstance()->CallHookAtPoint("pre_makeurl", array($host, $uri));
		if ($ret != null)
			return $ret;
		else {
			if(count(self::$named_params) > 0 || count($extend_named) > 0) {
				if($force_own_params == true)
					$named_params = $extend_named;
				else
					$named_params = array_merge(self::$named_params, $extend_named);
				$named_params_list = array();
				foreach($named_params as $key=>$value) {
					if(is_array($value)) {
						$value = implode("|", $value); // convert to string
					}
                    if(!empty($value))
					    $named_params_list[] = "$key" . $config["namedparam_char"] . "$value";
				}
				$uri_named_params = implode("/", $named_params_list);
				return "http://$host/$uri/$uri_named_params";
			}
			return "http://$host/$uri";
		}
	}
}

?>
