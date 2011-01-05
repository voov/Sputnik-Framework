<?php
     /**
     * Sputnik Form Plugin
     * @version 2.0
     * @author Daniel Fekete - Voov Ltd.
     */ 
	require_once "sputnik/IPlugin.php";
	
	class AuthPlugin implements IPlugin {
		private $baseObject;
		
		public $minimumLevel;
		
		public function __construct() {
			
		}
		
		public function SetBaseObject(&$base) {
			$this->baseObject =& $base;
			//var_dump($this);
		}
		
		public function OnLoad() {
			$this->baseObject->auth_plugin = $this;

		}
		
		public function RunOnEnter() {
			if (!$this->CheckUser($this->minimumLevel)) {
				// Show GateKeeper
				Sputnik::GetRunningInstance()->view->display("gatekeeper");
				//$this->baseObject->view->display("gatekeeper");
				exit;
			}			
		}		
		
		/**
		 * LoginUser
		 * Bel�pteti a felhaszn�l�t, ha tal�l ilyen felhaszn�l�n�vvel, �s jelsz�val felhaszn�l�t
		 * Ha bel�p, akkor be�ll�tja a "userid" session-t a felhaszn�l� ID-j�ra
		 * @return 
		 * @param $username Object
		 * @param $password Object
		 */
		public function LoginUser($username, $password) {
			global $config; // tal�ljuk meg a $config glob�lis v�ltoz�t
			$user_table = $config["user_table"];
			$user_table_username = $config["user_table_username"];
			$user_table_password = $config["user_table_password"];
			//echo "SELECT * FROM `$user_table` WHERE `$user_table_username`='$username' AND `$user_table_password`='$password'";
			$result = $this->baseObject->db->Query("SELECT * FROM `$user_table` WHERE `$user_table_username`='$username' AND `$user_table_password`='$password'");
			//var_dump($result);
			if ($result->length() > 0) $this->baseObject->GetSession()->userid = $result->id;
		}
		
		/**
		 * CheckUser
		 * @return bool igaz, ha a felhaszn�l�nak megvan a minimum szintje
		 * @param $minimum_level int az a minimum szint, amivel a felhaszn�l� authentik�lhat� az oldalra
		 * @param $inherit bool ha igaz, akkor a megadott szint felett �ll� felhaszn�l�k is authentik�lhat�ak
		 */
		private function CheckUser($minimum_level, $inherit = true) {
			global $config;
			if ($minimum_level == 0) return true; // a 0.-ik szint alapb�l eneged�lyezett
			$session = $this->baseObject->GetSession();
			if (!isset($session->userid)) return false;
			$user_table = $config["user_table"];
			$user_table_level = $config["user_table_level"];
			
			if ($inherit == true) {
				$result = $this->baseObject->db->Query("SELECT `$user_table_level` FROM `$user_table` WHERE `id`='$session->userid' AND `$user_table_level` >= '$minimum_level'");
			}
			else
				$result = $this->baseObject->db->Query("SELECT `$user_table_level` FROM `$user_table` WHERE `id`='$session->userid' AND `$user_table_level` = '$minimum_level'");
			

			if ($result->length() > 0) return true;
			else return false;
		}
	}
?>