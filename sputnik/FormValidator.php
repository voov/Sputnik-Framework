<?php
/**
 * FormValidator class
 * Sputnik Framework r3
 * @version 3.0
 * @author Daniel Fekete - Voov Ltd.
 */
class FormValidator {

	
    private $arr = array();
    private $rules = array();
    private $titles = array();
    private $filter_chain = array();
    private $use_regex = false;
    private $error_strings = array();
    private $error_wrap = "<p>%s</p>";

    // error reporting members
    private $error_list = array();
    private $num_of_errors = 0;
    private $has_errors = false;
    private $error_message = "";

	// --
	private $currentRunningFunction = "";
	private static $currentInstance = null;

	public function __construct($arr=null, $use_regex=false) {
		if ($arr == null) $arr = $_POST;
		$this->arr = $arr;
		$this->use_regex = $use_regex;
		FormValidator::$currentInstance = $this;
	}

    /**
     * Merges one more array to the list
     * @param  $arr The Array to be merged
     * @return void
     */
    public function ArrayMerge($arr) {
        $this->arr = array_merge($this->arr, $arr);
    }

	/**
	 * Validate the user input
	 */
	public function Validate() {
		$is_valid = true;
		// Run the Pre-validator filter chain
		if (isset($this->filter_chain["pre"])) {
			foreach($this->filter_chain["pre"] as $field => $pre_filter) {
				$this->arr[$field] = $this->_RunAction($field, $pre_filter);
			}
		}
		// Run the validation rules
		foreach($this->rules as $field_name=>$rule_fields) {
			foreach($rule_fields as $expr) {
				if($this->_RunAction($field_name, $expr) == false) {
					// The validation failed on the field
					$is_valid = false;
					$this->num_of_errors += 1;
					$this->has_errors = true;
                    if (isset($this->titles[$field_name])) $field_trans = $this->titles[$field_name];
                    else $field_trans = $field_name;
					$error_message = Localization::_($this->error_strings[$this->currentRunningFunction], $field_trans);
					$this->error_list[$field_name] = $error_message;
					$this->error_message .= sprintf($this->error_wrap, $error_message);
				}
			}
		}

		// Run the Post-validator filter chain
		if (isset($this->filter_chain["post"])) {
			foreach($this->filter_chain["post"] as $field => $post_filter) {
				$this->arr[$field] = $this->_RunAction($field, $post_filter);
			}
		}
		return $is_valid;
	}


	/**
	 * Add filter before (pre) or after (post) the validator
	 * @param <type> $position
	 * @param <type> $field
	 * @param <type> $expr
	 */
	public function AddFilter($position="pre", $field="*", $expr="trim") {
		$fields = $this->_ParseFields($field);
		foreach($fields as $field_item)
			$this->filter_chain[$position][$field_item] = $expr;

		return $this;
	}

	/**
	 * Add validation rule
	 * @param <type> $field
	 * @param <type> $expr
	 */
	public function AddRule($field, $expr, $title="") {
		$fields = $this->_ParseFields($field);
        if($title != "")
            $this->titles[$field] = $title;
		if(!is_array($expr)) {
			$expr_list = explode(";", $expr);
			foreach($expr_list as $expr_item) {
				foreach($fields as $field_item)
					$this->rules[$field_item][] = $expr_item;
			}
		} else {
			foreach($fields as $field_item)
				$this->rules[$field_item][] = $expr;
		}
		// let chaining work
		return $this;
	}

	public function __call($method, $args) {
		// Dynamically load validators
		include_once "validators/defaults.php";

		// check if the running instance have this function
		if(method_exists(Sputnik::GetRunningInstance(), $method)) {
			return call_user_func_array(array(Sputnik::GetRunningInstance(), $method), $args);
		}
		// check if we already have the function
		if (function_exists($method))
			return call_user_func_array($method, $args);
		else {
			// we still don't have the needed function, no panic!
			if (is_file("validators/" . $method . ".php")) {
				include_once "validators/" . $method . ".php";
				if (function_exists($method)) return call_user_func_array($method, $args);
			}
		}
		trigger_error("No validator was found for '$method'", E_USER_WARNING);
		return false; // we have to return something
	}

	/**
	 * Sets the error string for a given function
	 * @param <type> $func
	 * @param <type> $err_string
	 */
	public function SetErrorString($func, $err_string, $force=true) {
		if($force == false && array_key_exists($func, $this->error_strings)) return;
		$this->error_strings[$func] = $err_string;
	}

	/**
	 * Sets the error string for a given function
	 * @param <type> $func
	 * @param <type> $err_string
	 */
	public static function SetMessage($func, $err_string) {
		if (FormValidator::$currentInstance != null)
			FormValidator::$currentInstance->SetErrorString($func, $err_string, false);
	}

	/**
	 * Set the string wrapping the error message
	 * It uses the sprintf library
	 * Default is: <p>%s</p>
	 * @param <type> $wrap_str
	 */
	public function SetErrorWrap($wrap_str) {
		$this->error_wrap = $wrap_str;
	}

    /**
     * Sets the translation titles
     * @param  $arr
     * @return void
     */
    public function SetTitles($arr) {
        $this->titles = array_merge($this->titles, $arr);
    }

	/**
	 * Gets the error string for the given variable
	 * @param <type> $field
	 * @return <type>
	 */
	public function GetError($field) {
		if (isset($this->error_list[$field])) return $this->error_list[$field];
		return false;
	}

	/**
	 * Gets the error string for the given variable
	 * @param <type> $field
	 * @return <type>
	 */
	public function IsValid() {
		if($this->has_errors) return false;
		return true;
	}

	/**
	 * Returns the error message
	 * @return <type>
	 */
	public function GetErrorMessage() {
		return $this->error_message;
	}


	/**
	 * Returns the working array
	 * @return <type>
	 */
	public function GetArray() {
		return $this->arr;
	}

	/**
	 * Gives back the list of fields on which the field expression matches
	 * @param <type> $field_string
	 */
	private function _ParseFields($field_string) {
		$field_buffer = array();
		$wildcards = array("*" => "(?:.*?)", "?" => "(?:.{1})", "+" => "(?:.+?)");
		//$regex_wildcard = array("(?:.*?)", "(?:.{1})", "(?:.+?)");
		// we don't want to use regular expressions by default
		$field_final = $field_string;
		if ($this->use_regex == false)
			$field_final = strtr($field_string, $wildcards);
        $matches = 0;
		foreach($this->arr as $key=>$value) {
			if(($match = preg_match("/^$field_final$/i", $key))) $field_buffer[] = $key;
            $matches += $match;
		}
        if($matches == 0) $field_buffer[] = $field_string;
		return $field_buffer;
	}

	/**
	 *
	 * @param <type> $field
	 * @param <type> $expr
	 * @return <type>
	 */
	private function _RunAction($field, $expr) {
        if (!isset($this->arr[$field])) {
            // set the field to empty value, so the error string could be
            // printed out
            $this->arr[$field] = "";
        }
		$param_array = array($this->arr[$field]);
		if (is_array($expr)) {
			// Easy .. we have an array we can call
			$func_array = array_slice($expr, 0, 2);
			$this->currentRunningFunction = $func_array[1];
			if (count($expr) > 2) $param_array = array_merge($param_array,array_slice($expr, 2));
			return call_user_func_array($func_array, $param_array);
		}
		// Parse the expression
		// check for parameters
		if(($ps_index = strpos($expr, "[")) !== false) {
			$pe_index = strpos($expr, "]", $ps_index);
			$func_name = substr($expr, 0, $ps_index);
			$this->currentRunningFunction = $func_name;
			$parameter_string = substr($expr, $ps_index+1, $pe_index-$ps_index-1);
			$param_array = array_merge($param_array, explode(",", $parameter_string));
		} else {
			$func_name = $expr;
			$this->currentRunningFunction = $func_name;
		}
		if (function_exists($func_name)) {
			// No need to dynamically load function
			return call_user_func_array($func_name, $param_array);
		} else {
			// Load function from our own library
			return call_user_func_array(array($this, $func_name), $param_array);
		}

	}

	public function RepostAll() {
		$instance = Sputnik::GetRunningInstance();
		$instance->session->SetFlashdata("repost_validator", serialize($_POST));
	}

	public static function RenderRepost($arr_name="post") {
		$instance = Sputnik::GetRunningInstance();
		if (!$instance->session->GetFlashdata("repost_validator")) return;
		$repost_data = unserialize($instance->session->GetFlashdata("repost_validator"));
        $instance->view->{$arr_name} = $repost_data;
	}

	/*
	 * Include the most common validator function(s) so that it doesn't need to be
	 * dynamically loaded
	 */

	public function required($str) {
		if ($str == "") {
			$this->SetErrorString("required", "The field '<b>%s</b>' is required!", false);
			return false;
		}
		return true;
	}


}
?>
