<?php

/**
 * Session Adapter Interface
 * @version 3.0
 */
interface ISessionAdapter {
	public function Get($var);
	public function Set($var, $value, $ttl=0);
	public function Clear($var);
	public function Is_set($var);
	public function GetSessions();
}

/**
 * Helper functions for Session Adapters
 * @author Daniel Fekete
 * @version 3.0
 */
class SessionAdapterHelper {
	/**
	 * Returns the current user hash string
	 * @return <string> user hash
	 */
	static function GetUserHash() {
		$user_agent = $_SERVER["HTTP_USER_AGENT"];
		$user_ip = $_SERVER["REMOTE_ADDR"];
		$user_string = $user_agent . "|" . $user_ip;
		return md5($user_string);
	}

	/**
	 * Generate a session ID
	 * @return <type> session id
	 * @todo Set Cookie
	 */
	static function GenerateSessionID() {
		$session_id = md5(uniqid());
		//setco
		return $session_id;
	}
}
?>