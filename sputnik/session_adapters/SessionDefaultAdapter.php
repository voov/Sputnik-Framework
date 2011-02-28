<?php
require_once "sputnik/ISessionAdapter.php";
ini_set('session.use_trans_sid', false);

class SessionDefaultAdapter implements ISessionAdapter {

	function  __construct() {
		// Setup default PHP session
		
		session_start();
		header("Cache-control: private");
		
	}

	function Get($var) {
		return $_SESSION[$var];
	}

	function Set($var, $value, $ttl=0) {
		
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

	function GetSessions() {
		return $_SESSION;
	}
}

?>
