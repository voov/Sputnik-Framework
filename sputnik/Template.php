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

		if ($this->_caching == true) {
			$output = $this->_getCache($file);
		} else
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
		$html = file_get_html($this->_templateDir . "/$file");
		$blocks = $html->find("div[src]");
		foreach($blocks as $key=>$block) {
			// Fordítsuk le a blokkokat
			$replaceHtml = file_get_html($this->_templateDir . "/" . $block->src);
			$this->_compileBlocks($replaceHtml); // Compile all child blocks
			$block->outertext = $replaceHtml;
		}
		$this->_compileBlocks($html); // Compile the root node
		$e = $html->find("body");

		//echo "Outertext" . $e[1]->outertext;
		/*if ($config["load_jquery_version"])
			$e->outertext = $e->outertext . "<script type=\"script/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/" . $config["load_jquery_version"] . "/jquery.min.js\"></script>";
		*/
		return $html;
	}

	function _getOutput($file, $template=true) {

		// embed HTML builder to template
		$this->_variables["html"] = $this->html;
		

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


	/**
	 * Egyszerű email küldés
	 */

	private function html2text($html) {
		$tags = array (
			   0 => '~<h[123][^>]+>~si',
			   1 => '~<h[456][^>]+>~si',
			   2 => '~<table[^>]+>~si',
			   3 => '~<tr[^>]+>~si',
			   4 => '~<li[^>]+>~si',
			   5 => '~<br[^>]+>~si',
			   6 => '~<p[^>]+>~si',
			   7 => '~<div[^>]+>~si',
		);
		$html = preg_replace($tags,"\n",$html);
		$html = preg_replace('~</t(d|h)>\s*<t(d|h)[^>]+>~si',' - ',$html);
		$html = preg_replace('~<[^>]+>~s','',$html);
		// reducing spaces
		$html = preg_replace('~ +~s',' ',$html);
		$html = preg_replace('~^\s+~m','',$html);
		$html = preg_replace('~\s+$~m','',$html);
		// reducing newlines
		$html = preg_replace('~\n+~s',"\n",$html);
		return $html;
	}

	function SMTPMail($to, $from, $subject, $body, $smtp_id=0) {
		global $config;
		$newLine = "\r\n";
		$serverName = preg_replace('/www/', '', $_SERVER["HTTP_HOST"]);
		//connect to the host and port
		$conn = fsockopen($config["smtp_server"][$smtp_id], $config["smtp_port"][$smtp_id], $errno, $errstr, 45);
		$smtpResponse = fgets($conn, 4096);

		if(empty($conn)) {
			$output = "Failed to connect: $smtpResponse";
			echo $output;
			return;
		}


		//you have to say HELO again after TLS is started
		fputs($conn, "HELO $serverName". $newLine);
		$smtpResponse = fgets($conn, 4096);

		//request for auth login
		fputs($conn,"AUTH LOGIN" . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//send the username
		fputs($conn, base64_encode($username) . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//send the password
		fputs($conn, base64_encode($password) . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//email from
		fputs($conn, "MAIL FROM: <$from>" . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//email to
		fputs($conn, "RCPT TO: <$to>" . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//the email
		fputs($conn, "DATA" . $newLine);
		$smtpResponse = fgets($conn, 4096);

		//construct headers
		$headers = "MIME-Version: 1.0" . $newLine;
		$headers .= $body;

		//observe the . after the newline, it signals the end of message
		fputs($conn, "To: $to\r\nFrom: $from\r\nSubject: $subject\r\n$headers\r\n.\r\n");
		$smtpResponse = fgets($conn, 4096);

		// say goodbye
		fputs($conn,"QUIT" . $newLine);
		$smtpResponse = fgets($conn, 4096);


		fclose($conn);
	}

	function SendHTMLMail($file, $to, $from, $subject) {
		$random_hash = md5(uniqid());
		$string = $this->fetch($file);

		$headers = "From: $from";
		$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"VOOV-temp-".$random_hash."\"";

		$message = "--VOOV-temp-" . $random_hash . "\r\n";
		$message .= "Content-Type: text/plain; charset=\"utf8\" \r\n";
		$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		//$message .= "A levelező program nem támogatja a HTML alapú levelek megjelenítését.";

		$string_nl = eregi_replace('<br[[:space:]]*/?[[:space:]]*>',chr(13).chr(10),$string);

		//$message .= strip_tags($string_nl);
		$message .= $this->html2text($string);
		$message .= "\r\n\r\n";

		$message .= "--VOOV-temp-" . $random_hash . "\r\n";
		$message .= "Content-Type: text/html; charset=\"utf8\" \r\n";
		$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		$message .= $string;
		$message .= "\r\n--VOOV-temp-" . $random_hash . "--";

		if (is_array($to)) {
			foreach($to as $to_item)
				$mail = mail($to_item, $subject, $message, $headers);
		} else {
			//$mail = mail($to, $subject, $message, $headers);
			$this->SMTPMail($to, $from, $subject, $headers.$message);
		}
		if (!$mail) error_log("Nem sikerült elküldeni a levelet!");
	}
}




?>