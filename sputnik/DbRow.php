<?php
class DbRow implements Iterator {

	private $fields = array();

	public function __construct(array $arr = null) {
		if ($arr != null) {
			// megadtunk neki t�mb�t
			foreach($arr as $key=>$value) {
				$this->fields[$key] = $value;
			}
		}
	}

	public function __get($n) {
		if (isset($this->fields[$n])) {
			return $this->fields[$n];
		}
	}

	public function __set($n, $value) {
		$this->fields[$n] = $value;
	}

	public function SetFields($fields) {
		$this->fields = $fields;
	}

	public function GetFields() {
		return $this->fields;
	}

	public function rewind() {
		reset($this->fields);
	}

	public function current() {
		$row = current($this->fields);
		return $row;
	}

	public function key() {
		$key = key($this->fields);
		return $key;
	}

	public function next() {
		$next = next($this->fields);
		return $next;
	}

	public function valid() {
		$var = $this->current() !== false;
		return $var;
	}
}
?>
