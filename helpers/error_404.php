<?php

/**
 * Show 404 error
 * @return void
 */
function error_404() {
	global $config;
	if(!headers_sent())
		header("Status: 404 Not Found");
	$error_str = file_get_contents($config["error_directory"] . "error_404.html");
	echo $error_str;
	exit;
}
 
