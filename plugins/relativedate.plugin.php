<?php

class RelativeDatePlugin implements IPlugin {
	
	private $baseObj;
	private $dateTime;
	private $config;

	function __construct() {
		$config = array(
			"MINUTE_TO" => "perc múlva",
			"MINUTE_AGO" => "perce",
			"HOUR_TO" => "óra múlva",
			"HOUR_AGO" => "órája",
			"DAY_TO" => "nap múlva",
			"DAY_AGO" => "napja",
			"WEEK_TO" => "hét múlva",
			"WEEK_AGO" => "hete"
		);
	}

	public function SetConfig($config) {
		$this->config = array_merge($this->config, $config);
	}

	public function SetBaseObject(&$base) {
		$this->baseObject =& $base;
	}

	public function OnLoad() {
		// ez a fajta értékátadás nem működik Sputnik 3-ban!
		//$this->baseObject->relativedate = $this;
	}

	public function ConvertDate($date) {
		$this->dateTime = strtotime($date);
		$delta = time() - $this->dateTime;
		if ($delta > 2419200) return date("Y-m-d H:i", $this->dateTime);
		
	}

	function __toString() {
		return "";
	}
}

?>
