<?php

define("IC_TOP_LEFT", 1);
define("IC_TOP_RIGHT", 2);
define("IC_BOTTOM_LEFT", 3);
define("IC_BOTTOM_RIGHT", 4);

class ImageCache {
	
	private $im_res;
	private $fname = false;
	private $cache_fname = false;
	private $cacheable = true;

	private $callStack = array();

	private $outputType = "jpg";
	private $outputMethods = array("jpg" => "imagejpeg", "gif" => "imagegif", "png" => "imagepng");



	public function  __construct($fname, $cacheable=true) {
		if (!extension_loaded('gd') && !extension_loaded('gd2')) {
			trigger_error("GD is not loaded", E_USER_WARNING);
			return false;
		}
		
		$this->cacheable = $cacheable;
		$this->fname = $fname;
		return $this;
	}

	/**
	 * Every public call gets pushed into the call stack
	 * This way, we can cache all image modification calls, so it only has to be done
	 * once
	 * @param  $method
	 * @param  $arguments
	 * @return void
	 */
	public function __call($method, $arguments) {
		$this->callStack[] = array($method, $arguments);
		return $this;
	}


	/**
	 * Executes the call stack
	 * @return void
	 */
	public function Execute() {
		if(!is_file($this->fname)) return false;
		$this->im_res = imagecreatefromstring(file_get_contents($this->fname)) or
				die("Cannot open '$this->fname'");
		foreach($this->callStack as $stack) {
			// $stack[0] = method, $stack[1] = arguments
			call_user_func_array(array($this, "IC_" . $stack[0]), $stack[1]);
		}
		// Save to cache
		$this->SaveImage();
		if($this->im_res) imagedestroy($this->im_res);
	}

	/**
	 * Gets the cache's filename based on the call stack
	 * @return string
	 */
	private function GetCacheName() {
		return md5($this->fname . "|" . serialize($this->callStack)) . "." . $this->outputType;
	}

	/**
	 * Outputs the image
	 * @param  $quality
	 * @param  $fname
	 * @return void
	 */
	private function OutputImage($quality, $fname=false) {
		if($fname == false) $fname = $this->cache_fname;
		return call_user_func($this->outputMethods[$this->outputType], $this->im_res, $fname, $quality);
	}

	private function GenerateCache() {
		global $config;
		if($this->cacheable == false) return false;
		$this->cache_fname = $config["imagecache_dir"] . "/" .  $this->GetCacheName(); // override the current filename

		if(!is_file($this->cache_fname) || $this->cacheable == false) {
			// There is no cache, generate
			$this->Execute();
			return true;
		}
		return false;
	}

	private function ReBuffer($img) {
		if(is_resource($this->im_res)) imagedestroy($this->im_res);
		$this->im_res = $img;
	}

	private function IC_Watermark($watermark_path, $position, $alpha=99) {
		$margin = 5;
		$iwidth = imagesx($this->im_res);
		$iheight = imagesy($this->im_res);

		$watermark = imagecreatefromstring(file_get_contents($watermark_path));
		//$watermark = imagecreatefrompng($watermark_path);
		imagealphablending($watermark, true);
		$watermark_width = imagesx($watermark);
		$watermark_height = imagesy($watermark);
		
		switch($position) {
			case IC_TOP_LEFT:
				$x = $margin;
				$y = $margin;
			break;
			case IC_TOP_RIGHT:
				$x = $iwidth - ($margin+$watermark_width);
				$y = $margin;
			break;
			case IC_BOTTOM_LEFT:
				$x = $margin;
				$y = $iheight - ($margin+$watermark_height);
			break;
			case IC_BOTTOM_RIGHT:
			default:
				$x = $iwidth - ($margin+$watermark_width);
				$y = $iheight - ($margin+$watermark_height);
			break;
		}
		imagealphablending($this->im_res, true);
		//TODO: bug in PHP GD2
		//imagecopymerge($this->im_res, $watermark, $x, $y, 0, 0, $watermark_width, $watermark_height, $alpha);
		imagecopy($this->im_res, $watermark, $x, $y, 0, 0, $watermark_width, $watermark_height);
		imagedestroy($watermark); // free up
	}

	private function IC_BW() {
		$total = imagecolorstotal( $this->im_res );
		for ( $i = 0; $i < $total; $i++ ) {
			//imagecolorset( $this->im_res, $i, 255, 0, 0);
		}
	}

	private function IC_BW_Slow() {
		$iwidth = imagesx($this->im_res);
		$iheight = imagesy($this->im_res);

		for($i=0;$i<$iwidth*$iheight;$i++) {
			$x = $i % $iwidth;
			$y = floor($i / $iheight);

			$rgb = imagecolorat($this->im_res, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;

			$color = ($r + $g + $b) / 3;
			imagesetpixel($this->im_res, $x, $y, imagecolorallocate($this->im_res, $color, $color, $color));
		}
	}

	private function IC_Sepia() {
		$total = imagecolorstotal( $this->im_res );
		for ( $i = 0; $i < $total; $i++ ) {
			$index = imagecolorsforindex( $this->im_res, $i );
			$red = ( $index["red"] * 0.393 + $index["green"] * 0.769 + $index["blue"] * 0.189 ) / 1.351;
			$green = ( $index["red"] * 0.349 + $index["green"] * 0.686 + $index["blue"] * 0.168 ) / 1.203;
			$blue = ( $index["red"] * 0.272 + $index["green"] * 0.534 + $index["blue"] * 0.131 ) / 2.140;
			imagecolorset( $this->im_res, $i, $red, $green, $blue );
		}
	}

	private function IC_ResizeImage($newx, $newy, $keep_ratio=true) {

		$iwidth = imagesx($this->im_res);
		$iheight = imagesy($this->im_res);

		if (($iwidth <= $newx) && ($iheight <= $newy)) {
			return $this;
		}

		if($keep_ratio) {
			$w = round($iwidth * $newx / $iheight);
			$h = round($iheight * $newy / $iwidth );
			$new_width = $newx;
			$new_height = $newy;

			if (($newy-$h) < ($newx-$w))
				$new_width = $w;
			else
				$new_height = $h;
		} else {
			$new_width = $newx;
			$new_height = $newy;
		}

		$ithumb = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($ithumb, $this->im_res, 0, 0, 0, 0, $new_width, $new_height, $iwidth, $iheight);

		$this->ReBuffer($ithumb);

		return $this;
	}

	private function IC_Crop($crop_array) {
		if(!is_array($crop_array)) $crop_array = unserialize($crop_array);
		$buffer = imagecreatetruecolor($crop_array[2], $crop_array[3]);
		imagecopy($buffer, $this->im_res, 0, 0, $crop_array[0], $crop_array[1], $crop_array[2], $crop_array[3]);

		$this->ReBuffer($buffer);
	}

	private function IC_CropToRatio($ratioX, $ratioY) {
		if ($ratioY > $ratioX) return $this;
		$ratio = $ratioY / $ratioX;

		$im_width = imagesx($this->im_res);
		$im_height = imagesy($this->im_res);
		$im_new_height = round($ratio * $im_width);

		$new_im = imagecreatetruecolor($im_width, $im_new_height);
		$start_from = round(($im_height - $im_new_height) / 2);
		imagecopy($new_im, $this->im_res, 0, 0, 0, $start_from, $im_width, $im_new_height);

		$this->ReBuffer($new_im);
		
		return $this;
	}

	private function IC_CropCenter($size) {
		$iwidth = imagesx($this->im_res);
		$iheight = imagesy($this->im_res);
		$orig_square_size = min($iwidth, $iheight);

		$src_x = round(($iwidth - $orig_square_size) / 2);
		$src_y = round(($iheight - $orig_square_size) / 2);

		$ithumb = imagecreatetruecolor($size, $size);
		imagecopyresampled($ithumb, $this->im_res, 0, 0, $src_x, $src_y, $size, $size, $orig_square_size, $orig_square_size);

		$this->ReBuffer($ithumb);

		return $this;
	}

	public function RenderImage() {
		/*header('Content-type: image/jpeg');
		echo imagejpeg($this->im_res);*/

		return $this;
	}

	public function SaveImage($fname=false) {
		$this->OutputImage(99, $fname);
		return $this;
	}

	/**
	 * Saves the changes to cache
	 * Automatically gets done in the new version, no need to call this function anymore
	 * @deprecated Since version 3.1.0
	 * @return ImageCache
	 */
	public function SaveToCache() {
		/*
		 * This part automatically gets done in the new version
		 */
		return $this;
	}

	public function GetFilename() {
		$this->GenerateCache();
		return $this->cache_fname;
	}

	public function __toString() {
		return $this->GetFilename();
	}


	/**
	 * Support deprecated functions from 2.0	
	 */
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
