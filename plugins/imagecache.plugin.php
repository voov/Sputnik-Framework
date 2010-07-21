<?php
 /**
 * Sputnik Image Cache Plugin
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 */   
require_once "sputnik/sp-plugin.php";
require_once "sputnik/sp-config.php";

class ImagecachePlugin implements IPlugin {

    private $_args;
    private $_key;
    private $baseObj;
	
    function __construct()
    {
       
    }
	
	private function resizeImage($path, $newpath,  $newx, $newy, $forcewidth=false, $forceheight=false) {
			if (!extension_loaded('gd') && !extension_loaded('gd2')) 
		    {
		        trigger_error("GD is not loaded", E_USER_WARNING);
		        return false;
		    }
		    $im = imagecreatefromjpeg($path) or die ("Nem lehet megnyitani a f�jlt!");
		    $iwidth = imagesx($im); $iheight = imagesy($im);
		
		    if (($iwidth <= $newx) && ($iheight <= $newy)) {
		        imagejpeg($im, $newpath, 100);
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
		    imagejpeg($ithumb, $newpath, 100);
		    imagedestroy($ithumb); imagedestroy($im);
	}
	
	function getImageFromCache($filename, $sizeX, $sizeY) {
		if (trim($filename) == "") return "";
		global $config;
		$cacheFilename = $config["image_cache_dir"]  . "/" . md5($filename . $sizeX . $sizeY) . ".jpg";
		if (!is_file($cacheFilename)) $returnImage = $this->createImageCache($filename, $sizeX, $sizeY);
		else $returnImage = $cacheFilename;
		return $returnImage;
	}	
	
	function createImageCache($filename, $sizeX, $sizeY) {
		global $config;
		$cacheFilename = $config["image_cache_dir"] . "/" . md5($filename . $sizeX . $sizeY) . ".jpg";
		if (is_file($cacheFilename)) return $cacheFilename; // M�r l�tezik, hejj!
		$this->resizeImage($filename, $cacheFilename, $sizeX, $sizeY);
		return $cacheFilename;
	}
	
	public function SetBaseObject(&$base) {
		$this->baseObject =& $base;
	}
	
	public function OnLoad() {
		$this->baseObject->imagecache = $this;
	}
	
	
}
?>