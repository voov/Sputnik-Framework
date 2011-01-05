<?php

/**
 * A Simple Request object doing simple XSS filtering
 *
 * @author Daniel Fekete
 */
class Request {
	static $instance = false;
	
	private function CleanString($string) {
		$string = str_replace(array("&amp;","&lt;","&gt;"),array("&amp;amp;","&amp;lt;","&amp;gt;"),$string);
		// fix &entitiy\n;
		$string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u',"$1;",$string);
		$string = preg_replace('#(&\#x*)([0-9A-F]+);*#iu',"$1$2;",$string);
		$string = html_entity_decode($string, ENT_COMPAT, "UTF-8");

		// remove any attribute starting with "on" or xmlns
		$string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $string);

		// remove javascript: and vbscript: protocol
		$string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $string);
		$string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $string);
		$string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu', '$1=$2nomozbinding...', $string);
		$string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $string);

		//remove any style attributes, IE allows too much stupid things in them, eg.
		//<span style="width: expression(alert('Ping!'));"></span>
		// and in general you really don't want style declarations in your UGC

		$string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])style[^>]*>#iUu', "$1>", $string);

		//remove namespaced elements (we do not need them...)
		$string = preg_replace('#</*\w+:\w[^>]*>#i',"",$string);
		
		return $string;
	}

	public function  __get($name) {
		return $this->CleanString($_REQUEST[$name]);
	}

	public function __isset($name) {
		return false;
	}

	function getInstance() {
		if (!Request::$instance) {
			Request::$instance = new Request;
		}
		return Request::$instance;
	}
}
?>
