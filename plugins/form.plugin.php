<?php
     /**
     * Sputnik Form Plugin
     * @version 2.2
     * @author Daniel Fekete - Voov Ltd.
     */   
	require_once "sputnik/IPlugin.php";
	include_once "class.inputfilter.php";
	
	if(!defined("FORMPLUGIN")) {
		define("FORMPLUGIN", 1);
		define("CHECK_SQL_DATE", "(19|20)[0-9]{2}[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])");
		define("CHECK_EMAIL", "^[a-zA-Z0-9._%-]+@(?:[a-zA-Z0-9-]+\.)+[a-zA-Z]{2,4}$");
		define("CHECK_NOT_EMPTY", "^[a-zA-Z0-9]+");
		define("CHECK_COMPLEX_PASSWORD", "\A(?=[-_a-zA-Z0-9]*?[A-Z])(?=[-_a-zA-Z0-9]*?[a-z])(?=[-_a-zA-Z0-9]*?[0-9])\S{6,}\z");
		define("CHECK_NUMBER", "[-+]?\b[0-9]+(\.[0-9]+)?\b");
	}
	
	class FormElement {
		private $elementName;
		private $elementText;
		private $formObject = null;
		
		
		public function __construct($string, $formObject, $elementName) {
			$this->elementText = $string;
			$this->formObject = $formObject;
			$this->elementName = $elementName;
		}
		
		public function __toString() {
			/*echo $this->elementText;*/
			return $this->elementText;
		}
		
		public function CheckFieldSize($minSize, $maxSize=0) {
			$bad = false;
			if (strlen($this->elementText) < $minSize) $bad = true;
			if (strlen($this->elementText) > $maxSize && $maxSize != 0) $bad = true;
			
			if ($bad == true) {
				$this->formObject->AddBadField($this->elementName);
				return null;
			} else
				return $this->elementText;
		}
		
		public function CheckFieldAgainstSQL($sql) { 
			$db = $this->formObject->GetBaseObject()->db;
			$real_sql = preg_replace('/\{var\}/', $this->elementText, $sql);
			$query = $db->Query($real_sql);
			
			if ($query->length() > 0) {
				$this->formObject->AddBadField($this->elementName);
				return null;
			} else
				return $this->elementText;
		}
		
		public function CheckField($expr) {
			/*echo preg_match("/$expr/", $this->elementText) . " preg_match(\"/$expr/\", $this->elementText)<br />";*/
			if(preg_match("/$expr/", $this->elementText) == 0) {
				$this->formObject->AddBadField($this->elementName);
				return null;
			} else
				return $this->elementText;
		}

		public function IsSubmit() {
			if (preg_match("/.*submit.*/", strtolower($this->elementName)) && !empty($this->elementText)) {
				return true;
			}
			return false;
		}

		public function CreatePermalink() {

			$from = array("ö","ü","ó","ő","ú","á","ű","í","é","Ö","Ü","Ó","Ő","Ú","Á","Ű","Í","É");
			$to   = array("o","u","o","o","u","a","u","i","e","O","U","O","O","U","A","U","I","E");
			$str = str_replace($from, $to, $this->elementText);

			//$clean = iconv('UTF-8', 'ASCII//TRANSLIT', utf8_encode($str));
			$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $str);
			$clean = strtolower(trim($clean, '-'));
			$clean = preg_replace("/[\/_|+ -]+/", "-", $clean);

			return $clean;

		}

		public function GetBoolean() {
			if (empty($this->elementText)) return "0";
			else return $this->elementText;
		}

		public function GetInteger() {
			return preg_replace('/[^0-9]+/', '', $this->elementText);
		}
	}
	
	class FormBadFields {
		public $badFields = array();
		
		public function __construct($arr) {
			$this->badFields = $arr;
			
		}

		public function  __get($name) {
			return $this->IsFieldBad($name);
		}
		
		public function HasBad() {
			/*print_r($this->badFields);*/
			return count($this->badFields) > 0;
		}
		
		public function IsFieldBad($fieldname) {
			return in_array($fieldname, $this->badFields);
		}
	}
	
	class FormPlugin implements IPlugin {
		private $enableXssClean = true;
		private $baseObject;
		private $badFields = array();
		private $post = array();

		public function __construct() {
			
		}
		
		public function AddBadField($fieldname) {
			if (!in_array($fieldname, $this->badFields))
				$this->badFields[] = $fieldname;
			/*print_r($this->badFields);*/
		}
		
		public function GetBadFields() {
			/*print_r($this->badFields);*/
			return new FormBadFields($this->badFields);
		}
		
		public function SetBaseObject(&$base) {
			$this->baseObject =& $base;
			//var_dump($this);
		}
		
		public function GetBaseObject() {
			return $this->baseObject;
		}
		
		public function OnLoad($postObj = null) {
			if ($postObj == null) $this->post = $_POST;
			else $this->post = $postObj;
			$this->baseObject->form = $this;

		}
		
		public function DisableXssClean() {
			$this->enableXssClean = false;
		}
		
		public function EnableXssClean() {
			$this->enableXssClean = true;
		}

		public function RepostFields() {
			foreach($this->post as $key=>$value) {
				$this->baseObject->view->{$key} = $value;
			} 
		}
		
		public function __get($var) {
		
			if ($this->enableXssClean == true) {
				try {
					$filter = new InputFilter();
					if (!isset($this->post[$var])) return new FormElement("", $this, $var);
					if (is_array($this->post[$var])) return $this->post[$var];
					return new FormElement($filter->process($this->post[$var]), $this, $var);
				} catch(Exception $e) {
					echo "$var Exception:<br />";
					echo $e;
				}
			}
			else {
				try {
					if (is_array($this->post[$var])) return $this->post[$var];
					return new FormElement($this->post[$var], $this, $var);
				} catch(Exception $e) {
					echo "$var Exception:<br />";
					echo $e;
				}
			}
		}

		/**
		 *	@deprecated
		 */
		public function IsSubmit() {
			foreach($this->post as $key => $value) {
				if (preg_match("/.*submit.*/", strtolower($key))) {
					return true;
				}
			}
			
			return false;
		}
		
		private function CleanXSS($val) { 
		   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed 
		   // this prevents some character re-spacing such as <java\0script> 
		   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs 
		   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val); 
		    
		   // straight replacements, the user should never need these since they're normal characters 
		   // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29> 
		   $search = 'abcdefghijklmnopqrstuvwxyz'; 
		   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
		   $search .= '1234567890!@#$%^&*()'; 
		   $search .= '~`";,:?+/={}[]-_|\'\\'; 
		   for ($i = 0; $i < strlen($search); $i++) { 
		      // ;? matches the ;, which is optional 
		      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 
		    
		      // &#x0040 @ search for the hex values 
		      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ; 
		      // &#00064 @ 0{0,7} matches '0' zero to seven times 
		      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ; 
		   } 
		    
		   // now the only remaining whitespace attacks are \t, \n, and \r 
		   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
		   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'); 
		   $ra = array_merge($ra1, $ra2); 
		    
		   $found = true; // keep replacing as long as the previous round replaced something 
		   while ($found == true) { 
		      $val_before = $val; 
		      for ($i = 0; $i < sizeof($ra); $i++) { 
		         $pattern = '/'; 
		         for ($j = 0; $j < strlen($ra[$i]); $j++) { 
		            if ($j > 0) { 
		               $pattern .= '('; 
		               $pattern .= '(&#[xX]0{0,8}([9ab]);)'; 
		               $pattern .= '|'; 
		               $pattern .= '|(&#0{0,8}([9|10|13]);)'; 
		               $pattern .= ')*'; 
		            } 
		            $pattern .= $ra[$i][$j]; 
		         } 
		         $pattern .= '/i'; 
		         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag 
		         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags 
		         if ($val_before == $val) { 
		            // no replacements were made, so exit the loop 
		            $found = false; 
		         } 
		      } 
		   } 
		   return $val; 
		}		
	}
?>
