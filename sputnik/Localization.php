<?php

/** <title Localization>
    
    Localization support for Sputnik Framework 3.0
    <code lang="php">
    Localization:SetLocale("de_DE");
    echo _("Register Now");
    </code>
    @version 3.0 
    @author Daniel Fekete                          */

require_once("MoReader.php");


class Localization {

	private $loc = false;
	private $mo_reader;
	static $instance = null;
	private $domains = array("base" => "");
	private $cur_domain = "base";

	/**
	 *
	 * @return <type> 
	 */
	public static function GetInstance() {
		if(self::$instance === null) self::$instance = new Localization();
		return self::$instance;
	}

	/** <autolink on>
	    <aliasof TranslateText>
	    
	    Shortcut function to TranslateText */
	public static function _($str, $args=array()) {
		$o = Localization::GetInstance();
		$args = func_get_args();
		return call_user_func_array(array($o, "TranslateText"), $args);
	}

	/**
	 * @param  $domain
	 * @param  $path
	 * @return void
	 */
	public function BindTextDomain($domain, $path) {
		$this->domains[$domain] = $path;
	}

	public function SetDomain($domain) {
		$this->cur_domain = $domain;
	}

	public function GetCurrentDomain() {
		return $this->cur_domain;
	}

	/** Translate text $str to currently set locale
	    @param str   The string to translate
	    @param args  optional arguments             */
	public function TranslateText($str, $args=array()) {
		global $config;
		$arguments = array_slice(func_get_args(), 1); // get all arguments from the second
		if($this->cur_domain == "base")
			$mo_file = $config["lang_directory"] . $this->loc . "/default.mo";
		else
			$mo_file = $this->domains[$this->cur_domain] . "/" . $config["lang_directory"] . $this->loc . "/default.mo";
		
		if ($this->loc == false || !is_file($mo_file)) return vsprintf($str, $arguments); // no loc, return original
		$mo = new MoReader($mo_file);
		$loc_str = $mo->GetString($str);
		if($loc_str == false) return vsprintf($str, $arguments); // no translation found, return original
		return (string) vsprintf($loc_str, $arguments);
	}

	/** Set the current locale
	 */
	public function SetLocale($loc) {
		$sys_locale = $this->GetLocale($loc);
		$locales = array($sys_locale, $sys_locale . ".utf8");
		setlocale(LC_ALL, $locales);
		$this->loc = $loc;
	}

	public function GetLocale($code, $start=0, $end=false) {
		require_once("config/locales.php");
		global $locales;
		if($end == false) $end = count($locales);
		
		$half = round(($start+$end) / 2);
		if(abs($end-$start) <= 1) {
			if($locales[$start]["code"] == $code) return $locales[$start]["locale"];
			return false;
		}

		$cmp = strcmp($code, $locales[$half]["code"]);

		if($cmp == 0) return $locales[$half]["locale"];
		elseif($cmp > 0) return $this->GetLocale($code, $half, $end);
		else return $this->GetLocale($code, $start, $half);
	}

	public function RewriteHook($response, $host, $uri) {
		//print_r(func_get_args());
		return "http://" . $host . "/" . $this->loc ."/" . $uri;
	}

	public static function SetLocaleFromURI($regex, $uri) {
		preg_match($regex, $uri, $matches);
		// Query the selected language from database, and set the
		// session
		$lang = Db::GetInstance()->Query("SELECT * FROM languages WHERE code='{$matches[1]}'");
		if($lang->Length() > 0)
			Sessions::GetInstance()->lang_id = $lang->id; // If there is a language found
		Sessions::GetInstance()->lang_code = $matches[1];
		$localization = Localization::GetInstance();
		$localization->loc = $matches[1];

		// Add hook
		Hooks::GetInstance()->RegisterFunction("pre_makeurl", array($localization, "RewriteHook"));

		return $matches[2];
	}
}

		
?>
