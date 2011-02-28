<?php

$config = array();

// Core beállítások
$config["is_production"] = false;
$config["plugin_directory"] = "plugins/";
$config["app_directory"] = "apps/";
$config["error_directory"] = "errors/";

// Adatbázis beállításai
$config["db_username"] = "username";
$config["db_password"] = "password";
$config["db_connect"] = "localhost";
$config["db_dbname"] = "username";

// Log beállításai
$config["log_directory"] = "/home/users/username/logs";
// Log és report levelek
// 1-es minimum, csak a legnagyobb hibákat jelentjük
// 4-es maximum, minden jelzést jelentünk
// Alapértelmezett érték: 2
$config["log_level"] = 2;
$config["report_level"] = 2;

// User adatbázis beállításai
$config["user_table"] = "users";
$config["user_table_username"] = "username";
$config["user_table_password"] = "password";
$config["user_table_level"] = "level";

// Template beállítások
$config["view_template"] = "html_template";
$config["view_cache"] = "html_cache";
$config["set_fullpath"] = false;
$config["view_fullpath"] = "http://" . $_SERVER["HTTP_HOST"] . "/";
$config["load_jquery_version"] = "1.4.2";

// Modulok beállításai
$config["module_template"] = "module_template";

// Image cache
$config["enable_imagecache"] = true;
$config["imagecache_dir"] = "image_cache";
$config["imagecache_controller"] = "image_cache";

// SMTP beállítások
$config["smtp_server"][0] = "smtp.server.com";
$config["smtp_port"][0] = "25";
$config["smtp_username"][0] = "smtp_username";
$config["smtp_password"][0] = "smtp_password";

// Session
// Set the default adapter
//$config["session_adapter"] = "SessionMySQLAdapter";
$config["session_adapter"] = "SessionDefaultAdapter";
$config["session_table"] = "sessions";
$config["session_auth_user"] = true;


?>
