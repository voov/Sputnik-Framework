<?php

/**
 * @name CSV Reader
 * VOOV Ltd.
 * @author Daniel Fekete
 * @copyright 2008
 */
 
 
class CSVReader {
	
	var $has_header = true;
	var $lines = array();
	var $delim_char;
	var $array_pointer=0;
	var $startSeparatorPosition;
	
	function setDelimChar($d_char) {
		$this->delim_char = $d_char;
	}
	
	function GetCSVField($data) {
		// Bonyolultabb függvény
		
		if ($this->startSeparatorPosition == strlen($data) - 1) {
			$this->startSeparatorPosition++;
			return "";
		}
		
		$fromPos = $this->startSeparatorPosition+1;
		
		// Ha idézõjeles field, akkor ...
		if ($data{$fromPos} == "\"") {
			if ($fromPos == strlen($data) -1) {
				$fromPos++; // Itt lehet BUG! -> referenciaként kellene átadni a startSeparatorPosition-t?
				return "\"";
			}
			
			$nextSingleQuote = $this->FindSingleQuote($data, $fromPos+1);
			$this->startSeparatorPosition = $nextSingleQuote+1;
			return str_replace("\"\"", "\"", substr($data, $fromPos+1, $nextSingleQuote-$fromPos-1));
		}
		
		// Menjünk a következõ vesszõig :D
		$nextComma = strpos($data, $this->delim_char, $fromPos);
		if ($nextComma === false) {
			$this->startSeparatorPosition = strlen($data);
			return substr($data, $fromPos);
		} else {
			$this->startSeparatorPosition = $nextComma;
			return substr($data, $fromPos, $nextComma-$fromPos);
		}
		
	}
	
	
	function getFields($line) {
		$buffer = array();
		$this->startSeparatorPosition = -1;
		while ($this->startSeparatorPosition < strlen($line)) {
			array_push($buffer, $this->GetCSVField($line));
		}
			
		return $buffer;
		
	}
	
	function currentLine() {
		return $this->lines[$this->array_pointer-1];
	}
	
	function next() {
		if ($this->array_pointer == 0 && $this->has_header) $this->array_pointer++;
		$this->array_pointer++;
		$fields = $this->getFields($this->lines[$this->array_pointer-1]);
		if (!isset($fields[0]) || $fields[0] == "") return null;
		return $fields;
	}
	
	function FindSingleQuote($data, $startFrom) {
		$i = $startFrom - 1;
		while (++$i < strlen($data)) {
			$char = $data{$i};
			if ($char == "\"") {
				$next_char = $data{$i+1};
				if ($i < strlen($data) - 1 && $next_char == "\"") {
					// Ha dupla idézõjel van
					$i++;
					continue;
				} else
					return $i;	
			}	
		}
		
		return $i;
	}
	
	function CSVReader($fname, $delim_char=null) {
		$this->lines = file($fname);
		if (isset($delim_char)) $this->delim_char = $delim_char;
	}
}


?>