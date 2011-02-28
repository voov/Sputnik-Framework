<?php
/**
 * Sputnik RenderablePlugin
 * @version 3.0
 * @author Daniel Fekete - Voov Ltd.
 */

require_once 'IPlugin.php';

class PrintablePlugin implements IPlugin {
	// view variables
	private $_variables;
	private $base;
	private $name;

	// Functions that are currently unneeded
	function SetBaseObject(&$o) {}
	function OnLoad() {}

	function  __construct() {
		$this->base = Sputnik::GetRunningInstance();
		$this->_variables = array();
		$class_name = get_class($this);

		// set a value in the view based on the class name
		if(!isset($this->base->view->$class_name)) {
			$this->base->view->$class_name = $this;
		}
	}

	function Render() {return "";}

	function  __toString() {
		echo $this->Render();
	}

	public function SetModuleName($name) {
		$this->name = $name;
	}

	public function set($name, $value) {
		$this->_variables[$name] = $value;
	}

	public function get($name) {
		return $this->_variables[$name];
	}

	
}
?>
