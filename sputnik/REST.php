<?php

class REST {

    private static $instance=false;
	private $body = "";
	private $response_code=0;
    private $rest_hooks = array();
	private $status_codes = array(
		100=>'100 Continue',
		101=>'101 Switching Protocols',
		200=>'200 OK',
		201=>'201 Created',
		202=>'202 Accepted',
		203=>'203 Non-Authoritative Information',
		204=>'204 No Content',
		205=>'205 Reset Content',
		206=>'206 Partial Content',
		300=>'300 Multiple Choices',
		301=>'301 Moved Permanently',
		302=>'302 Found',
		303=>'303 See Other',
		304=>'304 Not Modified',
		305=>'305 Use Proxy',
		306=>'306 (Unused)',
		307=>'307 Temporary Redirect',
		400=>'400 Bad Request',
		401=>'401 Unauthorized',
		402=>'402 Payment Required',
		403=>'403 Forbidden',
		404=>'404 Not Found',
		405=>'405 Method Not Allowed',
		406=>'406 Not Acceptable',
		407=>'407 Proxy Authentication Required',
		408=>'408 Request Timeout',
		409=>'409 Conflict',
		410=>'410 Gone',
		411=>'411 Length Required',
		412=>'412 Precondition Failed',
		413=>'413 Request Entity Too Large',
		414=>'414 Request-URI Too Long',
		415=>'415 Unsupported Media Type',
		416=>'416 Requested Range Not Satisfiable',
		417=>'417 Expectation Failed',
		500=>'500 Internal Server Error',
		501=>'501 Not Implemented',
		502=>'502 Bad Gateway',
		503=>'503 Service Unavailable',
		504=>'504 Gateway Timeout',
		505=>'505 HTTP Version Not Supported');

	public function __construct() {
		
	}

	public function AddResponseBody($body) {
		$this->body .= $body;
        return $this;
	}

	public function GetResponseBody() {
		return $this->body;
	}

    public function SetCode($code) {
        $this->response_code = $code;
        return $this;
    }

	private function SendResponse() {
		$header = array();
		$header[] = "Status: HTTP/1.1 " . $this->status_codes[$this->response_code];
		$header[] = "Date: " . date("r");
		//$header[] = "Server: " . $_SERVER['SERVER_SIGNATURE'];
		//$header[] = "Location: " . $_SERVER['SERVER_NAME'];
        $header[] = "Content-Type: text/plain; charset=utf-8";
        $header[] = "Content-Length: " . strlen($this->body);
        //$header[] = "Connection: close";

        /*foreach($header as $header_item)
            header($header_item);*/

        echo $this->body;
	}

    public function Execute($code=false) {
        if($code !== false)
            $this->response_code = $code;
        $method = $this->GetRequestMethod();
        if(count($this->rest_hooks[$method]) > 0) {
            foreach($this->rest_hooks[$method] as $hook) {
                $buffer = call_user_func($hook);
                $this->AddResponseBody($buffer);
            }
        }
        $this->SendResponse();
    }

    public function AddHook($method, $func) {
        $this->rest_hooks[$method][] = $func;
        return $this;
    }

    public function GetRequestMethod() {
        return $_SERVER["REQUEST_METHOD"];
    }

	public function HasResponse() {
		return $this->response_code != 0;
	}

	static function GetInstance() {
        if(self::$instance == false) self::$instance = new REST();
        return self::$instance;
	}
}
