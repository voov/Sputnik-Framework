<?php
/* creates an html element, like in js */
class HtmlBuilder {
	/* vars */
	var $type;
	var $attributes;
	var $self_closers;
	var $innerHTML = "";
	var $set_value = "";
	//var $innerText = "";

	/* constructor */
	public function  __construct($elem_name="") {
		if($elem_name != "") $this->elem($elem_name);
		$this->self_closers = array('input','img','hr','br','meta','link');
	}

	public static function HtmlBuilder($elem_name) {
		return new HtmlBuilder($elem_name);
	}

	public function elem($type, $set_value="") {
		$this->clear();
		$this->type = strtolower($type);
		$this->set_value = $set_value;

		return $this;
	}

	/* get */
	public function get($attribute) {
		return $this->attributes[$attribute];
	}

	/* set -- array or key,value */
	public function attr($attribute,$value = '') {
		if(!is_array($attribute)) {
			$this->attributes[$attribute] = $value;
		}
		else {
			$this->attributes = array_merge($this->attributes,$attribute);
		}

		return $this;
	}

	public function value($val, $set_value="") {
		if ($this->type == "input" || $this->type == "option")
			$this->attr("value", $val);
		else
			$this->innerHTML = $val;
		$this->set_value = $set_value;
		return $this;
	}

	public function disabled($disabled=true) {
		if ($disabled == true) $this->attr("disabled", "disabled");
		else $this->remove("disabled");
		return $this;
	}

	public function html($text) {
		return $this->innerHTML($text);
	}

	public function innerHTML($text) {
		$this->innerHTML = $text;
		return $this;
	}

	public function __call($method, $args) {
		if (!method_exists($this, $method)) {
			$this->attr($method, $args[0]);
			return $this;
		}
		return false;
	}

	/* remove an attribute */
	function remove($att) {
		if(isset($this->attributes[$att])) {
			unset($this->attributes[$att]);
		}
	}

	/* clear */
	function clear() {
		$this->attributes = array();
	}

	/* inject */
	function inject($object) {
		if(@get_class($object) == __class__) {
			$this->attributes['text'].= $object->build();
		}
	}

	/* build */
	private function build() {
		//start
		$build = '<'.$this->type;

		if (empty($this->innerHTML)) $this->innerHTML = $this->attributes['text'];

		// check for selected values
		if(!is_array($this->set_value)) $this->set_value = array($this->set_value);
		foreach($this->set_value as $set_value) {
			if(in_array($set_value, $this->attributes)) {
				if ($this->type == "option") $this->attr("selected", "selected");
				if ($this->type == "input") $this->attr("checked", "checked");
			}
		}

		//add attributes
		if(count($this->attributes)) {
			foreach($this->attributes as $key=>$value) {
				if($key != 'text') {
					$build.= ' '.$key.'="'.$value.'"';
				}
			}
		}

		//closing
		if(!in_array($this->type,$this->self_closers)) {
			$build.= '>'. $this->innerHTML .'</'.$this->type.'>';
		}
		else {
			$build.= ' />' . $this->innerHTML;
		}

		//return it
		return $build;
	}

	/* spit it out */
	public function render() {
		echo $this->build();
	}

	public function  __toString() {
		return $this->build();
	}
}
?>
