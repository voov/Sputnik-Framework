<?php
require_once "FormValidator.php";
require_once "FormEvent.php";

class Form {

    private $notsafe_vars = array();
	private static $instance = false;
	private static $own_data = array();

    function __construct() {

        $this->notsafe_vars = $_POST;
        // Clean POST
        foreach ($_POST as $post_key => $post_value) {
            if (!preg_match("/^[a-z0-9:_\/-]+$/i", $post_key))
                trigger_error("Not safe var key ('$post_key')!");

	        $_POST[$post_key . ":unsafe"] = $post_value;
            $_POST[$post_key] = $this->XSS_Clean($post_value);
        }
		
    }

	public static function GetInstance() {
		if(self::$instance == false) self::$instance = new Form();
		return self::$instance;
	}

	public static function GI() {
		return self::GetInstance();
	}

	private function GetFileExt($filename) {
		return substr(strrchr($filename, '.'), 1);
	}

	private function GetUniqueFilename($filename) {
		
		$ext = $this->GetFileExt($filename);
		// explode the IP of the remote client into four parts
		$ipbits = explode(".", $_SERVER["REMOTE_ADDR"]);
		// Get both seconds and microseconds parts of the time
		list($usec, $sec) = explode(" ", microtime());

		// Fudge the time we just got to create two 16 bit words
		$usec = (integer) ($usec * 65536);
		$sec = ((integer) $sec) & 0xFFFF;

		// Fun bit - convert the remote client's IP into a 32 bit
		// hex number then tag on the time.
		// Result of this operation looks like this xxxxxxxx-xxxx-xxxx
		$uid = sprintf("%08x%04x%04x.%s", ($ipbits[0] << 24)
				| ($ipbits[1] << 16)
				| ($ipbits[2] << 8)
				| $ipbits[3], $sec, $usec, $ext);

		return $uid;
	}

	private function UploadArray($array, $upload_dir, $use_unique) {
		$buffer = array();
		foreach($array as $arr) {
			if($arr["error"] != UPLOAD_ERR_OK) return false;
			$filename = $use_unique ? $this->GetUniqueFilename($_FILES[$name]["name"]) : $_FILES[$name]["name"];
			$fulldir = $upload_dir ."/". $filename;
			$buffer[] = $fulldir;
			move_uploaded_file($arr["tmp_name"], $fulldir);
		}
		return $buffer;
	}

	public function UploadData($name, $upload_dir, $use_unique=false) {
		ini_set("upload_max_filesize", 12328960);
		ini_set("post_max_size", 12328960);
		if(!isset($_FILES[$name])) return false;
		if(isset($_FILES[$name][0]["name"])) return $this->UploadArray($_FILES[$name], $upload_dir, $use_unique);
		if($_FILES[$name]["error"] != UPLOAD_ERR_OK) {
			return false;
		}
		$filename = $use_unique ? $this->GetUniqueFilename($_FILES[$name]["name"]) : $_FILES[$name]["name"];

		$fulldir = $upload_dir ."/". $filename;
		if(!move_uploaded_file($_FILES[$name]["tmp_name"],$fulldir)) {
			trigger_error("Cannot upload file");
		}
		return $fulldir;
	}

	public function ClearAll() {
		// Clear all data sent from the browser
		//$_POST = array(); $_GET = array(); $_FILES = array();
	}


    public function GetNotSafe($name) {
        return $this->notsafe_vars[$name];
    }

    public function GetField($name, $use_regex) {
        $field_buffer = array();
        $wildcards = array("*" => "(?:.*?)", "?" => "(?:.{1})", "+" => "(?:.+?)");
        // we don't want to use regular expressions by default
        $field_final = $name;
        if ($use_regex == false)
            $field_final = strtr($name, $wildcards);
        foreach ($_POST as $key => $value) {
            if (preg_match("/^$field_final$/i", $key)) $field_buffer[$key] = $_POST[$key];
        }
        if(count($field_buffer) == 1) return $field_buffer[0];
        return $field_buffer;
    }

	public function __get($name) {
		return $_POST[$name];
	}

	public static function StartForm($name, $action, $method="post", $enctype="application/x-www-form-urlencoded") {
		$form = Form::Factory();
		//$action = URI::MakeURL($action);
		return "<form action='$action' method='$method' name='$name' id='$name' enctype='$enctype'>" .
				HtmlBuilder::HtmlBuilder("input")->type("hidden")->name(":input_token")->value(Sessions::GetInstance()->input_token) .
				HtmlBuilder::HtmlBuilder("input")->type("hidden")->name(":input_name")->value($name);
	}

	public static function EndForm() {
		return "</form>";
	}

    public static function Factory() {
	    return self::GetInstance();
    }

	public static function GetValidator() {
		return new FormValidator();
	}

    public static function GenerateInputToken() {
	    if(Sessions::GetInstance()->input_token == false) {
			$token = uniqid();
			Sessions::GetInstance()->input_token = $token;
	    }
    }

	public static function OnSubmit($name, $func) {
		if($_POST[":input_name"] != $name || $_POST[":input_token"] != Sessions::GetInstance()->input_token) {
			return;
		}

		call_user_func($func, $_POST);
	}

    private function XSS_Clean($data) {

        if(is_array($data)) {
            return $data;
            /*foreach($data as $value) {
                $this->XSS_Clean($value);
            }*/
            
        }
        $data = urldecode($data);

        // Fix &entity\n;
        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

        // Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

        // Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do
        {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	        //$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        }
        while ($old_data !== $data);

        // we are done...
        return $data;
    }


}

?>