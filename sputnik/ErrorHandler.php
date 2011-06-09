<?php
$global_errors = array();
function SputnikErrorHandler($errno, $errstr, $errfile, $errline) {
	global $config;
	global $global_errors;
	$file = $config["error_directory"] . "general_error.html";

	$faulty_file = file($errfile, FILE_SKIP_EMPTY_LINES);
	//echo $errline . "<br />";
	$fault_subset = array_slice($faulty_file, $errline-5, min((count($faulty_file)), 10), true);
	$vars = array("errno" => $errno, "errstr" => $errstr, "errfile" => $errfile, "errline" => $errline, "fault" => $fault_subset);
	//echo $errstr . " " . $config["log_level"];
	switch ($errno) {
		case E_ERROR:
		case E_USER_ERROR:
		case E_COMPILE_ERROR:
		case E_PARSE:
			$vars["errtype"] = "Fatal Error";
			break;
		case E_WARNING:
		case E_COMPILE_WARNING:
		case E_CORE_WARNING:
		case E_USER_WARNING:
			$vars["errtype"] = "Warning";
			if ($config["log_level"] < 2) return;
			break;

		case E_USER_NOTICE:
		case E_NOTICE:
			$vars["errtype"] = "Notice";
			if ($config["log_level"] < 3) return;
			break;

		default:
			$vars["errtype"] = "Unknown";
			if ($config["log_level"] <= 3) return;
			break;
	}
	$global_errors[] = $vars;
	
	return false;
}

function SputnikShutdownFunction() {
	global $config;
	global $global_errors;
	if(is_null($e = error_get_last()) === false) {
		
		SputnikErrorHandler($e["type"], $e["message"], $e["file"], $e["line"]);
	}
	if (count($global_errors) == 0) return;
	$file = $_SERVER["DOCUMENT_ROOT"] . "/" . $config["error_directory"] . "general_error.html";
	if (file_exists($file) && $config["is_production"] == false) {
		ob_start();
		$errors = $global_errors;
		include ($file);
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
	}
}

error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ALL);
//set_error_handler("SputnikErrorHandler");
//register_shutdown_function("SputnikShutdownFunction");

?>
