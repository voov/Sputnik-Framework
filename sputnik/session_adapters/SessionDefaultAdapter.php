<?php
require_once "sputnik/ISessionAdapter.php";

class SessionDefaultAdapter implements ISessionAdapter {

	function  __construct() {
		// Setup default PHP session
		ini_set('session.use_trans_sid', false);
		session_start();
		header("Cache-control: private");
		
	}

	function Get($var) {
		return $_SESSION[$var];
	}

	function Set($var, $value, $ttl) {
		
		if (!session_is_registered($var)) {
			// We don't have the session registered yet
			session_register($var);
		}

		if ($value == null) {
			// Clear the session
			$this->Clear($var);
		} else {
			// Set session
			$_SESSION[$var] = $value;
		}
	}

	function Clear($var) {
		unset($_SESSION[$var]);
		session_unregister($var);
	}

	function Is_set($var) {
		return isset($_SESSION[$var]);
	}
}

?>
