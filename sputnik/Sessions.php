<?php
/**
 * Sputnik Session Class
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 * 
 */



class Sessions {

	static $instance = false;
	private $session_adapter = null;

	function  __construct() {
		global $config;
		require_once "session_adapters/" . $config["session_adapter"] . ".php";
		$this->session_adapter = new $config["session_adapter"]();
	}
	
	/**
	 * Ezzel kapunk meg egy Session v�ltoz�t
	 * @return
	 * @param $var Object Session v�ltoz�
	 */
	function __get($var) {
		return $this->session_adapter->Get($var);
	}


	/**
	 * Be�ll�t egy session v�ltoz�t
	 * Amennyiben null-t adunk meg neki, akkor kit�rli a session-t
	 * @return
	 * @param $var Object melyik session v�ltoz�
	 * @param $val Object v�ltoz� �rt�ke
	 */
	function __set($var, $val) {
		$this->session_adapter->Set($var, $val);
	}


	/**
	 * Visszaadja, hogy az adott param�ter l�tezik-e
	 * isset() magic method
	 * @return bool param�ter l�tezik?
	 * @param $var Object
	 */
	function __isset($var) {
		return $this->session_adapter->Is_set($var);
	}


	function ClearSession($name) {
		$this->session_adapter->Clear($name);
	}

	/**
	 * Visszaadja a session oszt�ly egy statikus instance-j�t
	 * @return
	 */
	function getInstance() {
		if (!Sessions::$instance) {
			Sessions::$instance = new Sessions;
		}
		return Sessions::$instance;
	}
}
?>
