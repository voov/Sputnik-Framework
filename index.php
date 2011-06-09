<?php
define("SPUTNIK_VERSION", "3.1.0");
define("USE_SUBSITES", true); //set this to false if you want to disable subsite support
define("USE_REST", true);
define("USE_OAUTH_2", true);



//error_reporting(E_ALL ^ E_DEPRECATED);

require_once "sputnik/Controller.php";
require_once "sputnik/ErrorHandler.php";

if(USE_REST == true) {
	require_once "sputnik/RESTClient.php";
	require_once "sputnik/REST.php";
}


if(USE_SUBSITES == true) {
	require_once "sputnik/Subsites.php";
	Subsites::GetInstance()->RouteSubsite();
}

if(USE_OAUTH_2 == true) {
	require_once "sputnik/OAuth2.php";
}



Form::GenerateInputToken();
Sputnik::GetInstance()->Dispatch();



?>
