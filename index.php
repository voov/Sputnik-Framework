<?php
require_once "sputnik/Controller.php";
require_once "sputnik/Db.php";
require_once "sputnik/ErrorHandler.php";
require_once "sputnik/Mailer.php";

Sputnik::GetInstance()->Dispatch();
?>
