<?php

/**
 * Sputnik template engine
 * @author Daniel Fekete
 * @version 2.0
 * @copyright 2007-2008
 */

define("TEMPLATE_LOADED", 1);

require_once "config/config.php";
require_once "simple_html_dom.php";
require_once "HtmlBuilder.php";

ini_set('memory_limit', '128M');

class Template {

	var $_variables = array();
	var $_caching = true;
	var $_cacheDir;
	var $_cacheLifetime = 120;
	var $_longName = "php";
	var $_templateDir = "";
	var $_absolutePath = "";
	var $html;

	static $instance = false;

	function Template() {
		global $config;
		if (!isset($config["view_template"])) trigger_error("view_template is not configured!");
		$this->_cacheDir = $config["view_cache"];
		$this->_templateDir = $config["view_template"];
		$this->html = new HtmlBuilder();
		
		if($config["set_fullpath"] == true)
			$this->_absolutePath = $config["view_fullpath"];
		else
			$this->_absolutePath = "http://" . $_SERVER["HTTP_HOST"] . "/";
	}

	function assign($name, $value) {
		$variables = &$this->_variables;
		$variables[$name] = $value;
	}

	function __set($var, $val) {
		$this->assign($var, $val);
	}

	function display($file, $id = false) {
		echo $this->fetch($file, $id);
	}

	function fetch($file, $id = false) {
		
		/*if ($this->_caching == true) {
			$output = $this->_getCache($file);
		} else*/
		$output = $this->_getOutput($file);
		return isset($output) ? $output:false;

	}

	function setAbsolutePath($abspath) {
		$this->absolutePath = trim($abspath);
	}

	function resolve_href ($base, $href) {

		// href="" ==> current url.
		if (!$href) {
			return $base;
		}

		// href="http://..." ==> href isn't relative
		$rel_parsed = parse_url($href);
		if (array_key_exists('scheme', $rel_parsed)) {
			return $href;
		}

		// add an extra character so that, if it ends in a /, we don't lose the last piece.
		$base_parsed = parse_url("$base ");
		// if it's just server.com and no path, then put a / there.
		if (!array_key_exists('path', $base_parsed)) {
			$base_parsed = parse_url("$base/ ");
		}

		// href="/ ==> throw away current path.
		if ($href{0} === "/") {
			$path = $href;
		} else {
			$path = dirname($base_parsed['path']) . "/$href";
		}

		// bla/./bloo ==> bla/bloo
		$path = preg_replace('~/\./~', '/', $path);

		// resolve /../
		// loop through all the parts, popping whenever there's a .., pushing otherwise.
		$parts = array();
		foreach (
		explode('/', preg_replace('~/+~', '/', $path)) as $part
		) if ($part === "..") {
				array_pop($parts);
			} elseif ($part!="") {
				$parts[] = $part;
			}

		return (
			   (array_key_exists('scheme', $base_parsed)) ?
			   $base_parsed['scheme'] . '://' . $base_parsed['host'] : ""
			   ) . "/" . implode("/", $parts);

	}

	function setRewrite($origHref) {
		if ($this->_absolutePath == "" || strpos($origHref, "http:") ||
			   strpos($origHref, "mailto:") || strpos($origHref, "javascript:") ||
			   strpos($origHref, "href:")) return $origHref;
		$origHref = $this->resolve_href($this->_absolutePath, $origHref);
		return $origHref;
	}

	function _compileBlocks($html_node) {
		foreach($html_node->find("[href],[src],[action]") as $elem) {
			if ($elem->rel == "norewrite") {
				$elem->rel = preg_replace("/norewrite/", "", $elem->rel); // töröljük a rel-ből a norewrite-ot
				if ($elem->rel == "") $elem->rel = null; // ha ez volt az egyetlen, szedjük ki a attr-t
				continue;
			}
			if(isset($elem->src)) $elem->src = $this->setRewrite($elem->src);
			if(isset($elem->href)) $elem->href = $this->setRewrite($elem->href);
			if(isset($elem->action)) $elem->action = $this->setRewrite($elem->action);
		}
		//$html_node->find("comment")
	}

	function _compileScript($file) {
		//$file = $this->_templateDir . "/$file";
		//$buffer = file_get_contents($file); // Get contents of the file, setup temp buffer
		global $config;
		//$html = file_get_contents($this->_templateDir . "/$file");
		/*$html = file_get_html($this->_templateDir . "/$file");
		$blocks = $html->find("div[src]");
		foreach($blocks as $key=>$block) {
			// Fordítsuk le a blokkokat
			$replaceHtml = file_get_html($this->_templateDir . "/" . $block->src);
			$this->_compileBlocks($replaceHtml); // Compile all child blocks
			$block->outertext = $replaceHtml;
		}
		$this->_compileBlocks($html); // Compile the root node
		$e = $html->find("body");
		*/
		//echo "Outertext" . $e[1]->outertext;
		/*if ($config["load_jquery_version"])
			$e->outertext = $e->outertext . "<script type=\"script/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/" . $config["load_jquery_version"] . "/jquery.min.js\"></script>";
		*/
//		return $html;
		$file = $this->_templateDir . "/$file";
		$buffer = file_get_contents($file); // Get contents of the file, setup temp buffer

		if ($this->_absolutePath != "") {
			// TODO: preg_replace_callback!
			$buffer = preg_replace('/href=[\\"\\\'](?!(mailto:|javascript:|href:|http:))(.+?)[\\"\\\']/', 'href="' . $this->_absolutePath . '$2"', $buffer);
			$buffer = preg_replace('/src=[\\"\\\'](?!(mailto:|javascript:|href:|http:))(.+?)[\\"\\\']/', 'src="' . $this->_absolutePath . '$2"', $buffer);
			$buffer = preg_replace('/action=[\\"\\\'](?!(mailto:|javascript:|href:|http:))(.+?)[\\"\\\']/', 'action="' . $this->_absolutePath . '$2"', $buffer);

			//$buffer = preg_replace("/href[ ]=[\"\' ](?!(mailto:|javascript:|href:|http:))(.+?)[\"\']/", "href=\"" . $this->absolutePath . "$2\"", $buffer);
			//$buffer = preg_replace("/src[ ]=[\"\' ](?!(mailto:|javascript:|href:|http:))(.+?)[\"\']/", "src=\"" . $this->absolutePath . "$2\"", $buffer);
			$buffer = preg_replace('/\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*?)\\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)/', '\\$$1["$2"]', $buffer);
		}
		return $buffer;
	}

	function _getOutput($file, $template=true) {

		// embed HTML builder to template
		$this->_variables["html"] = $this->html;
		
		
		if (strpos($file, ".") === FALSE) {
			// Find the extension
			$extensions = array("html", "php", "htm");
			foreach($extensions as $ext) {
				if (is_file($this->_templateDir . "/$file." . $ext)) $file .= "." . $ext;
			}
		}
		
		// set controller vars
		if (($front_instance = Sputnik::GetInstance()) != false) {
			$vars = get_object_vars($front_instance);
			foreach($vars as $var_key => $var_value) {
				if (!isset($this->$var_key)) $this->$var_key =& $var_value;
			}
		}

		extract($this->_variables);
		if ($template == true)
			$file = $this->_templateDir . "/$file";

		if (file_exists($file)) {
			ob_start();
			include ($file);
			$output = ob_get_contents();
			ob_end_clean();
		}
		else {
			trigger_error("Nem lehet megnyitni a  '$file' template fájlt.", E_USER_ERROR);
		}

		return !empty($output) ? $output:false;
	}

	function setCacheDir($dir) {
		//$dir = realpath($dir);

		if (is_dir($dir) && is_writable($dir)) {
			$this->_cacheDir = $dir;
		}
		else {
			trigger_error("A cache könyvtár ('$dir') nem létezik, vagy nem lehet bele írni.", E_USER_WARNING);
			$this->_cacheDir = "";
			$this->_caching = false;
		}
	}

	function setTemplateDir($dir) {
		if (is_dir($dir)) {
			$this->_templateDir = $dir;
		}
		else {
			trigger_error("A template könyvtár ('$dir') nem létezik.", E_USER_WARNING);
			$this->_templateDir = "";
		}
	}

	function _createCache($file) {
		if ($this->_caching == true && $this->_cacheDir != "") {
			// Ha kell cacheln�nk
			$cacheFileName = $this->_cacheDir . "/" . md5($file) . ".cache.php";
			$output = $this->_compileScript($file);
			$f = fopen($cacheFileName, "wb") or die ("Nem tudja megnyitni a cache fájlt!");
			fwrite($f, $output);
			fclose($f);
		}

		return isset($output) ? $output : false;
	}

	function _getCache($file) {
		$cacheFileName = $this->_cacheDir . "/" . md5($file) . ".cache.php";

		if (is_file($file) && is_file($cacheFileName)) {
			clearstatcache(); // Clear PHP File Status Cache
			if (filemtime($file) > filemtime($cacheFileName)) {
				$this->_createCache($file); // Vissza is adunk egy friss v�ltoztatot
				$cached = $this->_getOutput($cacheFileName, false);
			} else {
				$cached = $this->_getOutput($cacheFileName, false);
			}
		} else {
			$this->_createCache($file);
			$cached = $this->_getOutput($cacheFileName, false);
		}

		return isset($cached) ? $cached : false;
	}

	function getInstance() {
		if (!Template::$instance) {
			Template::$instance = new Template;
		}
		return Template::$instance;
	}

	/**
	 * HTML kód beillesztő segédfüggvények
	 */
	function HTMLOption($value, $text, $selected_value=null) {
		if ($selected_value == $value) echo "<option value=\"$value\" selected=\"selected\">$text</option>";
		else echo "<option value=\"$value\">$text</option>";
	}

	function HTMLCheckbox($name, $value, $text, $selected_value=null, $class="") {
		if ($selected_value == $value) echo "<input type=\"checkbox\" name=\"$name\" value=\"$value\" class=\"$class\" checked=\"checked\" /> $text";
		else echo "<input type=\"checkbox\" name=\"$name\" value=\"$value\" class=\"$class\" /> $text";
	}

	function HTMLRadio($name, $value, $text, $selected_value=null, $class="") {
		if ($selected_value == $value) echo "<input type=\"radio\" name=\"$name\" value=\"$value\" class=\"$class\" checked=\"checked\" /> $text";
		else echo "<input type=\"radio\" name=\"$name\" value=\"$value\" class=\"$class\" /> $text";
	}

	function HTMLForwardFields($exclude_fields=array()) {
		foreach($_POST as $key=>$value) {
			if (in_array($key, $exclude_fields)) continue; // Ha nem akarjuk megjeleníteni, akkor lépjünk tovább
			$value = htmlentities(stripslashes($value));
			echo "\t<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
		}
	}


}




?>