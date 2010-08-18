<?php

class URI {
	static function RedirectToReferer() {
		$referer = $_SERVER["HTTP_REFERER"];
		if (headers_sent() == false)
			header("Location: $referer");
	}

	static function Redirect($uri) {
		if (headers_sent() == false)
			header("Location: " . URI::MakeURL($uri));
	}

	static function MakeURL($uri) {
		$host = $_SERVER["HTTP_HOST"];
		return "http://$host/$uri";
	}
}

?>
