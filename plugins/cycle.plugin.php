<?
 /**
 * Sputnik Cycle Plugin
 * @version 2.0
 * @author Daniel Fekete - Voov Ltd.
 */   
require_once "sputnik/sp-plugin.php";

class CyclePlugin implements IPlugin {

    private $_args;
    private $_key;
    private $baseObj;
	
    function __construct()
    {
        $this->_args = func_get_args();
        $this->_key = -1;
    }
	
	public function SetBaseObject(&$base) {
		$this->baseObject =& $base;
	}
	
	public function OnLoad() {
		$this->baseObject->cycle = $this;	
	}
	
	public function AddCycles() {
        $this->_args = func_get_args();
        $this->_key = -1;		
	}
	
	public function LastString() {
		return $this->_args[$this->_key];
	}
    
    function __toString()
    {
        return (string) isset($this->_args[$this->_key += 1]) ?
            $this->_args[$this->_key] :
            $this->_args[$this->_key = 0] ;
    }
}
?>