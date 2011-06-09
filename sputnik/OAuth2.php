<?php
/**
 * OAuth2
 * Created by Daniel Fekete.
 * 2011 Copyright VOOV Ltd.
 * User: user
 * Date: 2011.06.06.
 * Time: 13:15
 */

/*
 * SendRequest
 * GetPermission->GetAccessToken->SetAccessToken->API
 * SetAccessToken->API
 */
 
class OAuth2 {
	private $settings = array();
	private $token = false;

	public function __construct($settings = array()) {
		$this->SetSettings($settings);
	}

	public function GetPermission($scope) {
		$client_id = $this->settings["client_id"];
		$client_secret = $this->settings["client_secret"];
		$redirect_uri = $this->settings["redirect_uri"];
		$url_query = array(
			"client_id" => $client_id,
			//"client_secret" => $client_secret,
			"redirect_uri" => $redirect_uri,
			"scope" => $scope
		);

		$url = $this->settings["permission_url"] . "?" . http_build_query($url_query);
		header("Connection: close");
		header("Location: $url");
		exit;
	}

	public function GetAccessToken($code = false) {
		$token = Sessions::GetInstance()->Get($this->settings["client_id"] . "_token");
		//echo $token;
		if($token == false && $code) {
			// token is not in session and we have auth code
			$url_query = array(
				"client_id" => $this->GetSetting("client_id"),
				"client_secret" => $this->GetSetting("client_secret"),
				"redirect_uri" => $this->GetSetting("redirect_uri"),
				"code" => $code
			);

			$res = $this->SendRequest($this->GetSetting("token_url"), $url_query, "GET", "query");
			
			$token = $res["access_token"];
			$this->SetAccessToken($token);
			return $token;
		} elseif($token == true)
			return $token;
		return false;
	}

	public function SetAccessToken($token) {
		Sessions::GetInstance()->Set($this->settings["client_id"] . "_token", $token);
		$this->token = $token;
	}

	public function Api($method, $params=array()) {
		$token = $this->GetAccessToken();
		$params = array_merge($params, array("access_token" => $token));
		return $this->SendRequest($this->GetSetting("api_url") . $method, $params, "GET", "json");
	}

	public function SetSetting($name, $value) {
		$this->settings[$name] = $value;
	}

	public function SetSettings($settings) {
		$this->settings = array_merge($this->settings, $settings);
	}

	public function GetSetting($name) {
		return $this->settings[$name];
	}

	private function SendRequest($url, $data, $method="POST", $return="text") {
		$conn = curl_init();
		
		curl_setopt($conn, CURLINFO_HEADER_OUT, true);
		if($method == "POST" || $method == "DELETE" || $method == "PUT") {
			curl_setopt($conn, CURLOPT_URL, $url);
			curl_setopt($conn, CURLOPT_CUSTOMREQUEST, $method);
			//curl_setopt($conn, CURLOPT_POST, 1);
			curl_setopt($conn, CURLOPT_POSTFIELDS, http_build_query($data));
		} else {
			curl_setopt($conn, CURLOPT_URL, $url . "?" . http_build_query($data));
		}

		//curl_setopt($conn, CURLOPT_FOLLOWLOCATION  ,1);
		curl_setopt($conn, CURLOPT_HEADER, 0);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($conn, CURLINFO_HEADER_OUT, true);

		$response = curl_exec($conn);
		//echo curl_getinfo($conn, CURLINFO_HEADER_OUT);

		switch($return) {
			case "json":
				return json_decode($response);
			break;
			case "query":
				parse_str($response, $query_array);
				return $query_array;
			break;
			case "text":
			default:
				return $response;
			break;
		}

		return $response;
	}

	public static function Factory($adapter) {
		$adapter_file = "oauth/" . $adapter . ".php";

		if(!is_file($adapter_file)) {
			trigger_error("Could not find '$adapter' adapter!");
		}

		require_once $adapter_file;
		return new $adapter();
	}
}
