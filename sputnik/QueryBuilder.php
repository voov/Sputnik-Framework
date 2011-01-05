<?php
class TableJoin {
	private $tbl_name = "";
	private $joins = array();

	function  __construct($tbl_name) {
		$this->tbl_name = $tbl_name;
		return $this;
	}

	function Left() {
		$this->joins[] = "LEFT";
		return $this;
	}

	function Right() {
		$this->joins[] = "RIGHT";
		return $this;
	}

	function InnerJoin($tbl_name, $on) {
		$this->joins[] = "INNER JOIN $tbl_name ON $on";
		return $this;
	}

	function OuterJoin($tbl_name, $on) {
		$this->joins[] = "OUTER JOIN $tbl_name ON $on";
		return $this;
	}

	function  __toString() {
		return "$this->tbl_name " . implode(" ", $this->joins);
	}
}

class QueryBuilder {

	private $select_cols = array();
	private $where_conditions = array();
	private $limit_str = "";
	private $orders = array();
	private $from = "";


	function Select($cols="*") {
		if(!is_array($cols)) $cols = array($cols);
		$this->select_cols = array_merge($this->select_cols, $cols);
		return $this;
	}

	function From($tbl_name) {
		if($tbl_name instanceof TableJoin)
			$this->from = $tbl_name->__toString();
		else
			$this->from = $tbl_name;
		return $this;
	}

	function Where($condition) {
		if(!is_array($condition)) $condition = array($condition);
		$this->where_conditions = array_merge($this->where_conditions, $condition);
		return $this;
	}

	function Limit($from=null, $to=null) {
		if(isset($from) && isset($to))
			$this->limit_str = "LIMIT $from, $to";
		return $this;
	}

	function OrderBy($order_col, $order_dir) {
		if(!isset($order_col) || !isset($order_dir))
			return $this;
		$order_buffer = "$order_col $order_dir";
		$this->orders[] = $order_buffer;
		return $this;
	}

	function Render() {
		$select = implode(", ", $this->select_cols);

		$sql = "SELECT $select FROM {$this->from}";
		if(count($this->where_conditions) > 0) {
			$sql .= " WHERE ";
			$sql .= implode(" AND ", $this->where_conditions);
		}
		if(count($this->orders) > 0) {
			$sql .= " ORDER BY ";
			$sql .= implode(", ", $this->orders);
		}
		$sql .= " " . $this->limit_str;
		return $sql;
	}

	function  __toString() {
		return $this->Render();
	}

	
}
?>
