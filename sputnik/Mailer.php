<?php

/**
 * Sputnik mailer engine
 * @author Daniel Fekete
 * @version 3.0
 * @copyright 2007-2010
 */

require_once "config/config.php";

class Mailer {

	var $_variables = array();
	var $_templateDir = "";
	var $_absolutePath = "";
	var $_attachments = array();

	var $_cachedTemplate = "";
	var $_templateName = "";
	var $sendCount = 0;

	function Mailer($template_name) {
		$this->_templateName = $template_name;
		
	}

	function assign($name, $value) {
		$variables = &$this->_variables;
		$variables[$name] = $value;
	}

	function __set($var, $val) {
		$this->assign($var, $val);
	}

	function _getOutput($file, $template=true) {
		if (file_exists($file)) {
			ob_start();
			extract($this->_variables);
			include ($file);
			$output = ob_get_contents();
			ob_end_clean();
		}
		else {
			trigger_error("Nem lehet megnyitni a  '$file' template fájlt.", E_USER_ERROR);
		}

		return !empty($output) ? $output:false;
	}

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

	private function SMTPMail($to, $from, $subject, $body, $smtp_id=0) {
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
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
		fputs($conn, "To: $to\r\nFrom: $from\r\nSubject: $subject\r\n$headers\r\n.\r\n");
		$smtpResponse = fgets($conn, 4096);

		// say goodbye
		fputs($conn,"QUIT" . $newLine);
		$smtpResponse = fgets($conn, 4096);


		fclose($conn);
	}

	function AddAttachment($file, $imaginary_filename="") {
		if ($imaginary_filename=="") $imaginary_filename = basename($file);
		$this->_attachments[$imaginary_filename] = $file;
	}

	private function GetFileMIME($file) {
		$type = system("file -i -b $file");
		$split = split(";",$type);
		$type = trim($split[0]);
		return $type;
	}

	function Send($to, $from, $subject) {
		$random_hash = md5(uniqid());
		$string = $this->_getOutput($this->_templateName, false);

		$message = "From: $from";
		$message .= "\r\nContent-Type: multipart/alternative; boundary=\"VOOV-Sputnik-".$random_hash."\"\r\n";

		/* PLAIN TEXT */
		$message .= "--VOOV-Sputnik-" . $random_hash . "\r\n";
		$message .= "Content-Type: text/plain; charset=\"UTF-8\" \r\n";
		$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		$string_nl = eregi_replace('<br[[:space:]]*/?[[:space:]]*>',chr(13).chr(10),$string);

		$message .= $this->html2text($string);
		$message .= "\r\n\r\n";

		/* HTML TEXT */
		$message .= "--VOOV-Sputnik-" . $random_hash . "\r\n";
		$message .= "Content-Type: text/html; charset=\"UTF-8\" \r\n";
		$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";

		$message .= $string;
		$message .= "\r\n\r\n";

		/* CSATOLMÁNYOK */
		foreach($this->_attachments as $fname=>$attachment) {
			// adjuk hozzá a csatolmányokat
			$mime_type = $this->GetFileMIME($attachment);
			$message .= "--VOOV-Sputnik-" . $random_hash . "\r\n";
			$message .= "Content-Type: $mime_type; name=$fname\r\n";
			$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
			$message .= base64_encode(file_get_contents($attachment));
			$message .= "\r\n\r\n";
		}
		$message .= "--VOOV-Sputnik-" . $random_hash . "--";

		if (is_array($to)) {
			foreach($to as $to_item)
				$this->SMTPMail($to_item, $from, $subject, $message);
		} else {
			$this->SMTPMail($to, $from, $subject, $message);
		}
		$this->_attachments = array(); // clear the attachement array
		$this->_variables = array(); // clear variables
	}

	function SendHTMLMail($to, $from, $subject) {
		$this->Send($to, $from, $subject);
	}
}
?>