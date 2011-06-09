<?php

 
class MoReader {

    private $f;
    private $cur_pos=0;
    private $endian="N";


    private $total_elems;
    private $tables = array("original", "translated");

    function __construct($mofile) {
        $this->f = fopen($mofile, "rb");
	    if(!$this->f) {
		    trigger_error("Cannot open file: " . $mofile);
		    exit;
	    }
        // Check out endianness
        $magic_1 = "\x95\x04\x12\xde"; // big endian
        $magic_2 = "\xde\x12\x04\x95"; // little endian
        $magic_check = $this->ReadBytes(4);
        
        if($magic_check == $magic_2) $this->endian = "V";
        elseif ($magic_check != $magic_1 && $magic_check != $magic_2) {
            trigger_error("Not MO File!");
        }

        $this->revision = $this->ReadInt();
        $this->total_elems = $this->ReadInt();
        $original_pos = $this->ReadInt();
        $translated_pos = $this->ReadInt();

        
        // Read tables
        $this->SeekTo($original_pos);
        $this->tables["original"] = $this->ReadInt($this->total_elems * 2);
        $this->SeekTo($translated_pos);
        $this->tables["translated"] = $this->ReadInt($this->total_elems * 2);
    }

    function __destruct() {
        $this->Close();
    }

	public function GetString($str) {
		$i = $this->FindStringIndex($str, 0, $this->total_elems);
		if($i == false) return false;
	    return $this->ReadString("translated", $i);
	}


    private function Close() {
        if($this->f) fclose($this->f);
    }

    private function FindStringIndex($string, $start, $end) {
	    if(abs($end-$start) <= 1) {
			if($string == $this->ReadString("original", $start)) return $start;
		    return false;
	    }
		$half = (int)(($start + $end) / 2);
		$dir = strcmp($string, $this->ReadString("original", $half));
		if($dir == 0) return $half; // the string is exactly at the half
		if($dir > 0) return $this->FindStringIndex($string, $half, $end);
		else return $this->FindStringIndex($string, $start, $half);
    }

    private function ReadString($table, $index) {
        $length = $this->tables[$table][$index * 2 + 1];
        $offset = $this->tables[$table][$index * 2 + 2];
        $this->SeekTo($offset);
        return (string)$this->ReadBytes($length);
    }

    private function ReadInt($count=1) {
        $bytes = unpack($this->endian . $count, $this->ReadBytes(4 * $count));
        if($count == 1) {
            return array_shift($bytes);
        }
        return $bytes;
    }

    private function SeekTo($byte_pos) {
        fseek($this->f, $byte_pos);
        $this->cur_pos = $byte_pos;
    }

    private function ReadBytes($num_bytes) {
        fseek($this->f, $this->cur_pos); // seek to current position
        $buffer = "";
        while($num_bytes > 0) {
            $read_bytes = min($num_bytes, 8192);
            $chunk = fread($this->f, $read_bytes);
            $buffer .= $chunk;
            $num_bytes -= $read_bytes;
        }
        $this->cur_pos = ftell($this->f);
        return $buffer;
    }
}

