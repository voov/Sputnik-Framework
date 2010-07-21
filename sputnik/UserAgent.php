<?php



/**
 * Returns current user agent
 *
 * @author Daniel Fekete - VOOV Kft.
 * @copyright 2010(c) All Rights Reserved
 * @version 3.0
 */
class UserAgent {
	private $user_string;
	private $data;

	function  __construct() {
		$this->user_string = $_SERVER["HTTP_USER_AGENT"];
		$this->data = array();
	}

	private function GetData($data_name, $subt_array) {
		foreach($subt_array as $subt_key => $subt) {
			if (strpos($this->user_string, $subt_key) === TRUE)
				$this->data[$data_name] = $subt;
		}
	}
}
?>
