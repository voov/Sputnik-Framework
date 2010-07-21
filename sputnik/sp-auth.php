<?php
    /**
     * Sputnik Authentication
     * @version 2.0
     * @author Daniel Fekete - Voov Ltd.
     */
	
	
	require_once "sp-db.php";
	require_once "sp-sessions.php";
	
	class sp_auth {
		
		private $session = null;
		private $db = null;
		
		function __construct() {
			$session = sp_session::getInstance();
			$db = sp_db::getInstance();
		}
		
		/**
		 * LoginUser
		 * Belépteti a felhasználót, ha talál ilyen felhasználónévvel, és jelszóval felhasználót
		 * Ha belép, akkor beállítja a "userid" session-t a felhasználó ID-jára
		 * @return 
		 * @param $username Object
		 * @param $password Object
		 */
		function LoginUser($username, $password) {
			global $config; // találjuk meg a $config globális változót
			$user_table = $config["user_table"];
			$user_table_username = $config["user_table_username"];
			$user_table_password = $config["user_table_password"];
			
			$result = $db->Query("SELECT * FROM $user_table WHERE $user_table_username='$username' AND $user_table_password='$user_table_password'");
			if ($result->length > 0) $session->userid = $result->id;
		}
		
		/**
		 * CheckUser
		 * @return bool igaz, ha a felhasználónak megvan a minimum szintje
		 * @param $minimum_level int az a minimum szint, amivel a felhasználó authentikálható az oldalra
		 * @param $inherit bool ha igaz, akkor a megadott szint felett álló felhasználók is authentikálhatóak
		 */
		function CheckUser($minimum_level, $inherit = true) {
			if (!isset($session->userid)) return 0;
			$user_table = $config["user_table"];
			$user_table_level = $config["user_table_level"];
			
			if ($inherit == true)
				$result = $db->Query("SELECT $user_table_level FROM $user_table WHERE id='$session->userid' AND $user_table_level >= '$minimum_level'");
			else
				$result = $db->Query("SELECT $user_table_level FROM $user_table WHERE id='$session->userid' AND $user_table_level = '$minimum_level'");
			

			if ($result->length > 0) return true;
			else return false;
		}
	}
	
?>
