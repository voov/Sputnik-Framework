<?php
/**
 * Sputnik RenderablePlugin
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 */

require_once 'IPlugin.php';
require_once 'ImageCache.php';

class RenderablePlugin implements IPlugin {

	// view variables
	private $_variables;
	private $base;
	protected $_views;
	private $name;

	// Functions that are currently unneeded
	function SetBaseObject(&$o) {}
	function OnLoad() {
		
	}

	function  __toString() {
		echo $this->Render();
	}


	function  __construct() {
		$this->base = Sputnik::GetRunningInstance();
		$this->_views = array();
		$this->_variables = array();
		$class_name = get_class($this);

		// set a value in the view based on the class name
		if(!isset($this->base->view->$class_name)) {
			$this->base->view->$class_name = $this;
		}
	}

	public function SetModuleName($name) {
		global $config;
		$this->name = $name;
		$fd = @opendir($config["module_template"] . "/" . $this->name);
		while (($part = @readdir($fd)) == true) {
			if ($part != "." && $part != ".." && !is_dir($part)) {
				$info = pathinfo($part);
				$view_name =  basename($part,'.'.$info['extension']);
				$this->_views[$view_name] = $config["module_template"] . "/" . $this->name ."/" . $part;
			}
		}
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

	public function Render($view_name="main") {
		ob_start();
		extract($this->_variables); // in sandbox
		include $this->_views[$view_name];
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public function set($name, $value) {
		$this->_variables[$name] = $value;
	}

	public function get($name) {
		return $this->_variables[$name];
	}
}
?>
