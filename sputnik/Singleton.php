<?php

// A class to be used in all Singleton classes
class Singleton {
	protected static $instance=false;

	public static function GI() {
		return self::GetInstance();
	}

	public static function GetInstance() {
		if(self::$instance === false) self::$instance = new self();
		return self::$instance;
	}
}
