<?php

if(!defined("AUTH_GUEST")) define("AUTH_GUEST", 0);

class UserSession {
	private $timeout;
	private $user_id = AUTH_GUEST;
	private $group_id = AUTH_GUEST;
	private $username = "";
	private $subsites_id = AUTH_GUEST;

	
	public function SetTimeout($timeout) {
		$this->timeout = $timeout;
	}

	public function GetTimeout() {
		return $this->timeout;
	}

	public function SetUserId($user_id) {
		$this->user_id = $user_id;
	}

	public function GetUserId() {
		return $this->user_id;
	}

	public function SetGroupId($group_id) {
		$this->group_id = $group_id;
	}

	public function GetGroupId() {
		return $this->group_id;
	}

	public function SetUsername($username) {
		$this->username = $username;
	}

	public function GetUsername() {
		return $this->username;
	}

	public function SetSubsitesId($subsites_id) {
		$this->subsites_id = $subsites_id;
	}

	public function GetSubsitesId() {
		return $this->subsites_id;
	}
}
 
class Auth {
	static $instance = false;

	public function __construct() {
		$session = Sessions::GetInstance();

		Form::OnSubmit("authentication", array($this, "Authentication"));

		if(URI::GetNamedParam("user_logout") == "true") $this->Logout();
		if(URI::GetNamedParam("user_refresh") == "true") $this->RefreshSession();

		// There is no user_data yet, so we go on, and setup a guest account
		if(!$session->user_data || $this->IsTimeout()) {
			$user_data = new UserSession();
			$user_data->SetUserId(AUTH_GUEST);
			$user_data->SetTimeout(time());
		    $session->user_data = serialize($user_data);
		}

	}

	private function IsTimeout() {
		if(!Sessions::GetInstance()->user_data) return true;
		$data = $this->GetUserSession();
		if($data->GetTimeout() < time()) return true;
		return false;
	}

	public function RefreshSession() {
		$user_data = $this->GetUserSession();
		$user_data->SetTimeout(time()+18000);
		Sessions::GetInstance()->user_data = serialize($user_data);
		REST::GetInstance()->SetCode(200)->AddResponseBody(time()+18000)->Execute();
	}

	public function Logout() {
		Sessions::GetInstance()->user_data = null;
		URI::SetNamedParam("user_logout", null);
        URI::RedirectToReferer();
        exit;
		//URI::Redirect("admin/index");
	}

	public function Authentication($data) {
		//$salt = ""
		$username = $data["username"];
		$password = md5($data["password"]);
		
		$session = Sessions::GetInstance();
		$successful = false;
		Hooks::GetInstance()->CallHookAtPoint("pre_auth", array($username, $password));
		
		$user_data_query = Db::GetInstance()->Query("SELECT * FROM users WHERE username='$username' AND password='$password'");

		// There is no user_data yet, so we go on, and setup a real account
		if($user_data_query->Length() > 0) {
			$user_data = new UserSession();
			$user_data->SetUserId($user_data_query->id);
			$user_data->SetGroupId($user_data_query->group_id);
			$user_data->SetUsername($user_data_query->fullname);
			$user_data->SetTimeout(time()+18000);
			if(USE_SUBSITES)
				$user_data->SetSubsitesId($user_data_query->subsites_id);
		    $session->user_data = serialize($user_data);
		} else {
			Sessions::GetInstance()->SetFlashdata("authentication_statustext", Localization::_("Nem megfelelő felhasználónév/jelszó!"));
		}
		

		Hooks::GetInstance()->CallHookAtPoint("post_auth", array($username, $password, $successful));
		return $successful;
	}

	public function SetUserSession(UserSession $userdata) {
		Sessions::GetInstance()->user_data = serialize($userdata);
	}

	public function GetUserSession() {
		return unserialize(Sessions::GetInstance()->user_data);
	}
	

	public function IsGuest() {
		$user_data = $this->GetUserSession();
		return $user_data->GetUserId() == AUTH_GUEST;
	}

	static function GI() {
		return self::GetInstance();
	}

	static function GetInstance() {
		if(self::$instance == false) self::$instance = new self();
		return self::$instance;
	}
}
