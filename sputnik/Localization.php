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

	private static $loc;

	/** <autolink on>
	    <aliasof TranslateText>
	    
	    Shortcut function to TranslateText */
	public static function _($str, $args) {
		return Localization::TranslateText($str, $args);
	}

	/** Translate text $str to currently set locale
	    @param str   The string to translate
	    @param args  optional arguments             */
	public static function TranslateText($str, $args) {
	}

	/** Set the current locale
	    @param loc_text  The locale setting. Example\: "en\-US" */
	public static function SetLocale($loc_text) {
		setlocale("LC_ALL", $loc_text);
		Localization::$loc = $loc_text;
	}
}

		
?>
