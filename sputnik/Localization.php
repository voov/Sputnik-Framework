<?php

/** <title Localization>
    
    Localization support for Sputnik Framework 3.0
    <code lang="php">
    Localization:SetLocale("de_DE");
    echo _("Register Now");
    </code>
    @version 3.0 
    @author Daniel Fekete                          */

class Localization {

	private $loc;
	static $instance = null;

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
	public static function _($str, $args) {
		$o = Localization::GetInstance();
		return $o->TranslateText($str, $args);
	}

	/** Translate text $str to currently set locale
	    @param str   The string to translate
	    @param args  optional arguments             */
	public function TranslateText($str, $args) {
		
	}

	/** Set the current locale
	 */
	public function SetLocale($loc) {
		
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
