<?php

 
class Subsites {
	private $db;
	protected static $instance=false;
	private $subsite_cache = false;

	public function __construct() {
		$this->db = Db::GetInstance();
	}

	public function Hook_RestrictQuery($result, $qb, $restrict_to) {
		if($qb->CanUseSubsiteFilter() == false) return;
		if($restrict_to == false)
			$restrict_to = $this->GetSubsiteID();
		$qb->Where($qb->GetMasterTable() . ".subsites_id='$restrict_to'");
	}

	public function RestrictQuery($restrict_to=false) {
		Hooks::GetInstance()->RegisterFunction("pre_makequery", array($this, "Hook_RestrictQuery"), array($restrict_to));
	}

	public function RouteSubsite() {
		//$host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"];
		$host = $_SERVER["HTTP_HOST"]; // TODO: get default site
		if($this->subsite_cache === false)  {
			$this->db->url = $host;
			$this->subsite_cache = $this->db->Query("SELECT * FROM subsites WHERE url={url} LIMIT 0,1")->GetArray();
		}
		
	}

	public function GetAllSubsites() {
		return $this->db->Query("SELECT * FROM subsites ORDER BY name");
	}

	public function GetSubsiteData($var) {
		return $this->subsite_cache[$var];
	}

	public function GetSubsiteID() {
		return $this->subsite_cache["subsites_id"];
	}

	public static function GI() {
		return self::GetInstance();
	}

	public static function GetInstance() {
		if(self::$instance === false) self::$instance = new self();
		return self::$instance;
	}
}
