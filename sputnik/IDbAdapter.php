<?php


interface IDbAdapter {
	public function Connect($server, $username, $password);
	public function Disconnect();
	public function SwitchDb($db);
	public function Query($query_string);
	public function EscapeString($string);
	public function Info();
}
