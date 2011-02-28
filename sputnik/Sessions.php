<?php
/**
 * Sputnik Session Class
 * @version 3.0
 * @author Daniel Fekete - Voov Ltd.
 * 
 */



class Sessions {

	static $instance = false;
	private $session_adapter = null;
	private $deleteable_flash_data = array();

	function  __construct() {
		global $config;
		require_once "session_adapters/" . $config["session_adapter"] . ".php";
		
		$this->session_adapter = new $config["session_adapter"]();
		//error_log(print_r($_SESSION, true));
		// clear all flash data!
		$this->ClearFlashdata();
		$this->MarkFlashdata();
	}

	

	function ClearFlashdata() {
		foreach($this->session_adapter->GetSessions() as $session_key=>$session_value) {
			if(strpos($session_key, "!_flash:") !== false) $this->session_adapter->Clear($session_key);
		}
	}

	function MarkFlashdata() {
		foreach($this->session_adapter->GetSessions() as $session_key=>$session_value) {
			if(preg_match('/^_flash:(.+)/', $session_key, $regs)) {
				$value = $this->session_adapter->Get($session_key);
				$this->session_adapter->Clear($session_key);
				$this->session_adapter->Set("!_flash:" . $regs[1], $value);
			}
		}
	}
	
	/**
	 * Ezzel kapunk meg egy Session v�ltoz�t
	 * @return
	 * @param $var Object Session v�ltoz�
	 */
	function __get($var) {
		$value = $this->session_adapter->Get($var);
		if ($value != null)
			return $value;
		else
			return false;
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

	
	function SetFlashdata($var, $value) {
		$this->session_adapter->Set("_flash:" . $var, $value);
	}

	function GetFlashdata($var) {
		$value = $this->session_adapter->Get("!_flash:" . $var);
		if (!empty($value)) {
			return $value;
		} else {
			return false;
		}
	}

    function GetSessions() {
        return $this->session_adapter->GetSessions();
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
