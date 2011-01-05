<?php

require_once 'HtmlBuilder.php';
require_once 'ImageCache.php';

class Renderable {

	private $_variables;
	private $_renderables;
	private $template_file;
	private $template_dir;

	function  __construct($template="") {
		global $config;

		//echo nl2br(print_r(debug_backtrace(), true));
		//echo "construct";
		$this->_variables = array();
		$this->_renderables = array();
		$this->template_dir = $config["view_template"];
		if($template != "") {
			$this->template_file = $this->GetFilename($template);
		}
	}



	private function GetFilename($template_name) {
		$file = $this->template_dir . "/" . $template_name;
		if (strpos($file, ".") === FALSE) {
			// Find the extension

			$extensions = array("php", "html", "htm");
			foreach($extensions as $ext) {
				if (is_file($file . "." . $ext)) $file .=  "." . $ext;
			}
		}
		return $file;
	}

	/**
	 *
	 * @param <type> $name
	 * @param <type> $value
	 */
	protected function set($name, $value) {
		if(empty($value)) return;
		$this->_variables[$name] = $value;
	}

	/**
	 *
	 * @param <type> $name
	 * @return <type>
	 */
	protected function get($name) {
		return $this->_variables[$name];
	}

	/**
	 *
	 * @param <type> $name
	 * @param <type> $value
	 */
	public function  __set($name,  $value) {
		$this->set($name, $value);
	}

	/**
	 *
	 * @param <type> $name
	 * @return <type>
	 */
	public function  __get($name) {
		return $this->get($name);
	}


	/**
	 *
	 * @param <type> $name
	 * @param <type> $path
	 * @return <type>
	 */
	public function AddRenderable($name, $renderable) {
		if(is_object($renderable))
			$this->_renderables[$name] = $renderable;
		else
			$this->_renderables[$name] = new self($renderable);
		return $this->_renderables[$name];
	}


	/**
	 *
	 */
	public function Display($template="") {
		echo $this->Fetch($template);
	}

	/**
	 *
	 * @return <type>
	 */
	public function Fetch($template="") {
		if($template != "") {
			$this->template_file = $this->GetFilename($template);
		}
		return $this->Render();
	}

		/**
		 *
		 * @param <type> $fname
		 * @param <type> $size
		 * @param <type> $crop
		 */
	public function GetImageCache($fname, $size, $crop=false) {
		global $config;
		if($config["enable_imagecache"] == true) {
			$ic = new ImageCache();
			return $ic->GetImageFromCache($fname, $size, $size, $crop);
		} else {
			return $fname;
		}
	}

	public function  __toString() {
		return $this->Fetch();
	}


	/**
	 *
	 * @return <type>
	 */
	public function Render($template="") {
		if($template != "") {
			$this->template_file = $this->GetFilename($template);
		}
		ob_start();
		// first extract all renderables to sandbox
		extract($this->_renderables);
		extract($this->_variables); // in sandbox
		if (($front_instance = Sputnik::GetRunningInstance()) != false) {
			$vars = get_object_vars($front_instance);
			foreach($vars as $var_key => $var_value) {
				if (!isset($this->$var_key)) {
					$this->$var_key = null;
					$this->set($var_key, &$vars[$var_key]);
				}
			}
		}
		include $this->template_file;
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
}
?>
