<?php

 
class RESTClient {

	private $uri;
	private $data = false;
	private $additional_headers = array();

	public function __construct($uri) {
		$this->uri = $uri;
		return $this;
	}

	public function Get($return="text") {
		return $this->Execute("GET", $return);
	}

	public function Post($return="text") {
		return $this->Execute("POST", $return);
	}

	public function Put($return="text") {
		return $this->Execute("PUT", $return);
	}

	public function Delete($return="text") {
		return $this->Execute("DELETE", $return);
	}

	public function Execute($method="GET", $return="text") {
		return $this->CurlHttpRequest($this->uri, $method, $return);
	}

	public function AddBasicAuth($username, $password) {
		$this->AddExtraHeader(sprintf("Authorization: Basic %s", base64_encode($username.':'.$password)));
		$this->AddExtraHeader("Content-type: application/x-www-form-urlencoded");
		return $this;
	}

	public function AddData($data) {
		if(is_array($data)) {
			if($this->data == false) $this->data = array();
			$this->data = array_merge($this->data, $data);
		}
		else {
			if($this->data == false) $this->data = "";
			$this->data .= $data;
		}
		return $this;
	}

	public function AddExtraHeader($header) {
		$this->additional_headers[] = $header;
		return $this;
	}

	private function GetData() {
		if(is_array($this->data)) return http_build_query($this->data);
		return $this->data;
	}

	private function CurlHttpRequest($url, $method, $return) {

		$conn = curl_init();
		curl_setopt($conn, CURLINFO_HEADER_OUT, true);
		if($method == "POST" || $method == "DELETE" || $method == "PUT") {
			curl_setopt($conn, CURLOPT_URL, $url);
			curl_setopt($conn, CURLOPT_CUSTOMREQUEST, $method);
			//curl_setopt($conn, CURLOPT_POST, 1);
			curl_setopt($conn, CURLOPT_POSTFIELDS, $this->GetData());
		} else {
			curl_setopt($conn, CURLOPT_URL, $url . "?" . $this->GetData());
		}

		if(count($this->additional_headers) > 0) {
			curl_setopt($conn, CURLOPT_HTTPHEADER, $this->additional_headers);
		}
		//curl_setopt($conn, CURLOPT_FOLLOWLOCATION  ,1);
		curl_setopt($conn, CURLOPT_HEADER, 0);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($conn);
		$http_response = curl_getinfo($conn);
		if($http_response["http_code"] != "200") {
			// var_dump($http_response["request_header"]);
			
		}

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
	}

	private function HttpRequest($url, $method="GET", $return) {
		$params = array('http' => array(
			'method' => $method,
		));
		if($method == "POST") {
			if(is_array($this->data)) $this->data = http_build_query($this->data);
			$params["http"]["content"] = $this->data;
		} else {
			$url = $url . http_build_query($this->data);
		}
		if (count($this->additional_headers) > 0) {
			$params["http"]["header"] = implode("\r\n", $this->additional_headers);
		}
		
		$ctx = stream_context_create($params);
		$fp = fopen($url, 'rb', false, $ctx);
		if (!$fp) {
			throw new Exception("Problem with $url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
			throw new Exception("Problem reading data from $url, $php_errormsg");
		}
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
	}


	public static function Factory($uri) {
		return new RESTClient($uri);
	}
}
