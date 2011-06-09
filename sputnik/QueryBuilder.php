<?php
class TableJoin {
	private $tbl_name = "";
	private $master_table = "";
	private $joins = array();

	public static function TableJoin($tbl_name) {
		return new TableJoin($tbl_name);
	}

	function  __construct($tbl_name) {
		$this->tbl_name = $tbl_name;
		$this->SetMasterTable($tbl_name);
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

	public function SetMasterTable($master_table) {
		$this->master_table = $master_table;
	}

	public function GetMasterTable() {
		return $this->master_table;
	}
}

class QueryBuilder {

	private $select_cols = array();
	private $where_conditions = array();
	private $limit_str = "";
	private $orders = array();
	private $groups = array();
	private $from = "";
	private $master_table = "";
	private $subsite_filter = true;
	private $unions = array();

	public function __construct() {
		
		return $this;
	}

	public static function QueryBuilder() {
		return new QueryBuilder();
	}

	public static function SelectFrom($select="*", $from) {
		$q = new QueryBuilder();
		return $q->Select($select)->From($from);
	}

	public function SubsiteFilter($use) {
		$this->subsite_filter = $use;
		return $this;
	}

	public function CanUseSubsiteFilter() {
		return $this->subsite_filter;
	}


	function Select($cols="*") {
		if(!is_array($cols)) $cols = array($cols);
		$this->select_cols = array_merge($this->select_cols, $cols);
		return $this;
	}

	function From($tbl_name) {
		if($tbl_name instanceof TableJoin) {
			$this->SetMasterTable($tbl_name->GetMasterTable());
			$this->from = $tbl_name->__toString();
		}
		else {
			$this->SetMasterTable($tbl_name);
			$this->from = $tbl_name;
		}
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

	function OrderBy($order_col, $order_dir="asc") {
		if(!isset($order_col) || !isset($order_dir))
			return $this;
		$order_buffer = "$order_col $order_dir";
		$this->orders[] = $order_buffer;
		return $this;
	}

	function GroupBy($group_col) {
		if(!isset($group_col))
			return $this;
		$this->groups[] = $group_col;
		return $this;
	}

	function Union($qb) {
		$this->unions[] = $qb->Render();
		return $this;
	}

	function Render() {
		Hooks::GetInstance()->CallHookAtPoint("pre_makequery", array($this));
		$select = implode(", ", $this->select_cols);

		if(count($this->unions) > 1) {
			$unions = implode(") UNION (", $this->unions);
			$sql = "($unions)";
		} else {
			$sql = "SELECT $select FROM {$this->from}";
		}
		if(count($this->where_conditions) > 0) {
			$sql .= " WHERE ";
			$sql .= implode(" AND ", $this->where_conditions);
		}
		if(count($this->groups) > 0) {
			$sql .= " GROUP BY ";
			$sql .= implode(", ", $this->groups);
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

	public function SetMasterTable($master_table) {
		$this->master_table = $master_table;
	}

	public function GetMasterTable() {
		return $this->master_table;
	}


}
?>
