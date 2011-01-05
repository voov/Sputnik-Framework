<?php

class ImageCache {

	public function  __construct() {
		
	}

	private function ResizeImage($path, $newpath,  $newx, $newy, $crop=false) {
		if (!extension_loaded('gd') && !extension_loaded('gd2')) {
			trigger_error("GD is not loaded", E_USER_WARNING);
			return false;
		}
		$im = imagecreatefromstring(file_get_contents($path)) or die ("Nem lehet megnyitani a fájlt ('$path')!");
		$iwidth = imagesx($im);
		$iheight = imagesy($im);

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

		if($crop == false) {
			$ithumb = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($ithumb, $im, 0, 0, 0, 0, $new_width, $new_height, $iwidth, $iheight);
		} else {
			$square_size = max($new_width, $new_height);
			$orig_square_size = min($iwidth, $iheight);

			$src_x = round(($iwidth - $orig_square_size) / 2);
			$src_y = round(($iheight - $orig_square_size) / 2);

			$ithumb = imagecreatetruecolor($square_size, $square_size);
			imagecopyresampled($ithumb, $im, 0, 0, $src_x, $src_y, $square_size, $square_size, $orig_square_size, $orig_square_size);
		}

		imagejpeg($ithumb, $newpath, 100);
		imagedestroy($ithumb);
		imagedestroy($im);
	}

	public function RenderImage($filename, $sizeX, $sizeY, $crop=false) {
		header('Content-type: image/jpeg');
		echo file_get_contents($this->GetImageFromCache($filename, $sizeX, $sizeY, $crop));
	}

	public function GetImageFromCache($filename, $sizeX, $sizeY, $crop=false) {
		if (trim($filename) == "") return "";
		global $config;
		$is_crop = $crop == true ? "_crop" : "";
		$cacheFilename = $config["imagecache_dir"]  . "/" . md5($filename . $sizeX . $sizeY . $is_crop) . ".jpg";
		if (!is_file($cacheFilename)) $returnImage = $this->createImageCache($filename, $sizeX, $sizeY, $crop);
		else $returnImage = $cacheFilename;
		return $returnImage;
	}

	public function CreateImageCache($filename, $sizeX, $sizeY, $crop=false) {
		global $config;
		$is_crop = $crop == true ? "_crop" : "";
		$cacheFilename = $config["imagecache_dir"] . "/" . md5($filename . $sizeX . $sizeY. $is_crop) . ".jpg";
		if (is_file($cacheFilename)) return $cacheFilename; // Már létezik a fájl!
		$this->ResizeImage($filename, $cacheFilename, $sizeX, $sizeY, $crop);
		return $cacheFilename;
	}
}

?>
