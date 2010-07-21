<?
 /**
 * Sputnik Cycle Plugin
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 */   
require_once "sputnik/sp-plugin.php";

class QuerybuilderPlugin implements IPlugin {

    private $query;
    private $baseObj;
    private $endsWithNonQuery = false;
	
    function __construct()
    {
		$this->query = array();
    }
	
	public function SetBaseObject(&$base) {
		$this->baseObject =& $base;
	}
	
	public function OnLoad() {
		$this->query = array();
		$this->baseObject->querybuilder = $this;
	}

	public function AddWhere() {
		if (count($this->query) != 0) return $this;
		$this->query[] = "WHERE";
		return $this;
	}

	function AddQueryIf($var, $query) {
		if (isset($var) && $var != "") {
			return $this->AddQuery($query);
		}
	}

	public function AddQuery($query) {
	   $this->query[] = $query;
	   $this->endsWithNonQuery = false;
	   return $this;
	}

	public function AddAnd() {
		if (count($this->query) < 2 || $this->endsWithNonQuery == true) return $this;
		$this->endsWithNonQuery = true;
		$this->query[] = "AND";
		return $this;
	}

	public function AddOr() {
		if (count($this->query) < 2 || $this->endsWithNonQuery == true) return $this;
		$this->endsWithNonQuery = true;
		$this->query[] = "OR";
		return $this;
	}
	
	public function ClearQuery() {
		$this->query = array();
	}
    
    function __toString() {
	    //print_r($this->query);
		if($this->endsWithNonQuery == true) {
			$this->query = array_slice($this->query, 0, -1);
			$this->endsWithNonQuery = false;
		}
		
		if ($this->query[0] == "WHERE" && count($this->query) == 1) return "";
		$buffer = implode(" ", $this->query);
		return $buffer;
    }
}
?>