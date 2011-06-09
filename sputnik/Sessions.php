<?php
/**
 * Sputnik Session Class
 * @version 3.0
 * @author Daniel Fekete - Voov Ltd.
 * 
 */


require_once "Crypt.php";

class Sessions {

	static $instance = false;
	private $session_adapter = null;
	private $deleteable_flash_data = array();
	private $crypt = false;
	private $secondary_flash_cache = array();

	function  __construct() {
		global $config;
		require_once "session/" . $config["session_adapter"] . ".php";
		
		$this->session_adapter = new $config["session_adapter"]();
		$this->ClearFlashdata();
		$this->MarkFlashdata();

		if(!empty($config["session_key"])) {
			$this->crypt = new Crypt();
			$this->crypt->SetKey($config["session_key"]);
		}
	}

	

	function ClearFlashdata() {
		foreach($this->session_adapter->GetSessions() as $session_key=>$session_value) {
			if(strpos($session_key, "!_flash:") !== false) $this->session_adapter->Clear($session_key);
		}
	}

	function MarkFlashdata($exact_name=false) {
		foreach($this->session_adapter->GetSessions() as $session_key=>$session_value) {
			if(preg_match('/^_flash:(.+)/', $session_key, $regs)) {
				if($exact_name !== false && $regs[1] != $exact_name) continue;
				$value = $this->session_adapter->Get($session_key);
				$this->session_adapter->Clear($session_key);
				$this->session_adapter->Set("!_flash:" . $regs[1], $value);
			}
		}
	}

	public function Get($var) {
		if($this->crypt != false) {
			$value = $this->session_adapter->Get($var);
			return $this->crypt->Decrypt($value);
		} else {
			return $this->session_adapter->Get($var);
		}

	}

	public function Set($var, $value) {
		if($this->crypt != false) {
			$value = $this->crypt->Encrypt($value);
		}
		$this->session_adapter->Set($var, $value);
	}
	
	/**
	 * Ezzel kapunk meg egy Session v�ltoz�t
	 * @return
	 * @param $var Object Session v�ltoz�
	 */
	function __get($var) {
		$value = $this->Get($var);
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
		$this->Set($var, $val);
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
		$this->Set("_flash:" . $var, $value);
		$this->secondary_flash_cache[$var] = $value;
	}

	function GetFlashdata($var) {
		if(!empty($this->secondary_flash_cache[$var])) {
			// We have accessed the flashdata without reload, mark to delete
			$this->MarkFlashdata($var);
			return $this->secondary_flash_cache[$var];
		}
		$value = $this->Get("!_flash:" . $var);
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
	static function GetInstance() {
		if (!Sessions::$instance) {
			Sessions::$instance = new Sessions;
		}
		return Sessions::$instance;
	}
}
?>
