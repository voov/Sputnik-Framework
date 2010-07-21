<?php
   /**
     * Sputnik File Upload Plugin
     * @version 2.0
     * @author Daniel Fekete - Voov Ltd.
     */   
	require_once "sputnik/sp-plugin.php";
	
	class UploadPlugin implements IPlugin {
		private $baseObject;
		private $config = array();
		private $return_buffer = array();		
		
		public function __construct() {
			
		}
		
		public function SetBaseObject(&$base) {
			$this->baseObject =& $base;
			//var_dump($this);
		}
		
		public function OnLoad() {
			$this->baseObject->upload = $this;
		}		
		
		public function SetConfig(array $p_conf) {
			$this->config = $p_conf;
		}
		
		private function resizeImage($path, $newpath,  $newx, $newy, $forcewidth=false, $forceheight=false) {
			if (!extension_loaded('gd') && !extension_loaded('gd2')) 
		    {
		        trigger_error("GD is not loaded", E_USER_WARNING);
		        return false;
		    }
		    //$im = imagecreatefromjpeg($path) or die ("Nem lehet megnyitani a f�jlt!");
			$imageinfo = getimagesize($path);
			if ($imageinfo["channels"] == 4) die("A feltöltött kép nem RGB hanem CMYK formátumban lett elmentve, kérjük konvertálja át a képet egy kép konvertáló programmal!");
		    $im = imagecreatefromstring(file_get_contents($path)) or die ("Nem lehet megnyitani a fájlt ('$path')!");
			if ($im == FALSE) return;
			$iwidth = imagesx($im); $iheight = imagesy($im);
		
		    if (($iwidth <= $newx) && ($iheight <= $newy)) {
		        imagejpeg($im, $newpath, 95);
		        imagedestroy($im);
		        return;
		    }
			
			$w = round($iwidth * $newx / $iheight);
			$h = round($iheight * $newy / $iwidth );
		    $new_width = $newx;
		    $new_height = $newy;
		
		    if (($newy-$h) < ($newx-$w))
		        $new_width = $w;
		    else
		        $new_height = $h;
		
		    $ithumb = imagecreatetruecolor($new_width, $new_height);
		    imagecopyresampled($ithumb, $im, 0, 0, 0, 0, $new_width, $new_height, $iwidth, $iheight);
		    imagejpeg($ithumb, $newpath, 95);
		    imagedestroy($ithumb); imagedestroy($im);
		}		
		
		public function DoUpload($name = null) {
			$this->return_buffer = array(); // clear the return buffer
			foreach($_FILES as $key => $file) {
				if ($name != null && $key != $name) continue;
				$data_buffer = array();
				
				$filename = $file["name"];
				if (empty($filename)) continue;
				
				$filename = strtolower($filename);
				$filename_raw = substr($filename, 0, strrpos($filename, "."));
				$filename_ext = substr($filename, strrpos($filename, ".")+1);
				
				$images_ext = array("jpg", "jpeg", "gif", "png");
				
				if ($this->config["uniq_name"] == true)
					$filename_raw = uniqid();
					
				$fullpath = $this->config["upload_dir"] . "/" . $filename_raw . "." . $filename_ext;
				$thumbpath = $this->config["upload_dir"] . "/thumb_" . $filename_raw . "." . $filename_ext;
				
				move_uploaded_file($file["tmp_name"], $fullpath);
				
				// resize
				if (in_array($filename_ext, $images_ext) && $this->config["max_width"] > 0 && $this->config["max_height"] > 0) {
					if ($this->config["max_forcewidth"] == true)
						$this->resizeImage($fullpath, $fullpath, $this->config["max_width"], $this->config["max_height"], true);
					else if ($this->config["max_forceheight"] == true)
						$this->resizeImage($fullpath, $fullpath, $this->config["max_width"], $this->config["max_height"], false, true);					
					else
						$this->resizeImage($fullpath, $fullpath, $this->config["max_width"], $this->config["max_height"]);
					$data_buffer["is_image"] = true;
				}
				
				if (in_array($filename_ext, $images_ext) && $this->config["thumb_width"] > 0 && $this->config["thumb_height"] > 0) {
					if ($this->config["thumb_forcewidth"] == true)
						$this->resizeImage($fullpath, $thumbpath, $this->config["thumb_width"], $this->config["thumb_height"], true);
					else if ($this->config["thumb_forceheight"] == true)
						$this->resizeImage($fullpath, $thumbpath, $this->config["thumb_width"], $this->config["thumb_height"], false, true);					
					else
						$this->resizeImage($fullpath, $thumbpath, $this->config["thumb_width"], $this->config["thumb_height"]);					
					$data_buffer["thumbpath"] = $thumbpath;
					$data_buffer["thumbname"] = "thumb_" . $filename_raw . "." . $filename_ext; 
				}
				
				$data_buffer["filepath"] = $fullpath;
				$data_buffer["filename"] = $filename_raw . "." . $filename_ext;
				$data_buffer["filename_raw"] = $filename_raw;
				$data_buffer["filename_ext"] = $filename_ext;
				$this->return_buffer[] = $data_buffer;
			}
			
			return $this->return_buffer;
			
		}
		
		public function DoUploadArray($arr_name) {
			
			$this->return_buffer = array(); // clear the return buffer
			$arr = $_FILES[$arr_name];
			
			$count = count($arr["name"]);
			
			for($i=0; $i<$count; $i++) {
				
				$data_buffer = array();
				
				$filename = $arr["name"][$i];
				if (empty($filename)) continue;
				
				$filename = strtolower($filename);
				$filename_raw = substr($filename, 0, strrpos($filename, "."));
				$filename_ext = substr($filename, strrpos($filename, ".")+1);
				
				$images_ext = array("jpg", "jpeg", "gif", "png");
				
				if ($this->config["uniq_name"] == true)
					$filename_raw = uniqid();
					
				$fullpath = $this->config["upload_dir"] . "/" . $filename_raw . "." . $filename_ext;
				$thumbpath = $this->config["upload_dir"] . "/thumb_" . $filename_raw . "." . $filename_ext;
				
				move_uploaded_file($arr["tmp_name"][$i], $fullpath);
				
				// resize
				if (in_array($filename_ext, $images_ext) && $this->config["max_width"] > 0 && $this->config["max_height"] > 0) {
					$this->resizeImage($fullpath, $fullpath, $this->config["max_width"], $this->config["max_height"]);
					$data_buffer["is_image"] = true;
				}
				
				if (in_array($filename_ext, $images_ext) && $this->config["thumb_width"] > 0 && $this->config["thumb_height"] > 0) {
					$this->resizeImage($fullpath, $thumbpath, $this->config["thumb_width"], $this->config["thumb_height"]);
					$data_buffer["thumbpath"] = $thumbpath;
					$data_buffer["thumbname"] = "thumb_" . $filename_raw . "." . $filename_ext; 
				}
				
				$data_buffer["filepath"] = $fullpath;
				$data_buffer["filename"] = $filename_raw . "." . $filename_ext;
				$data_buffer["filename_raw"] = $filename_raw;
				$data_buffer["filename_ext"] = $filename_ext;
				$this->return_buffer[] = $data_buffer;
			}
			
			return $this->return_buffer;
			
		}				
	}
?>