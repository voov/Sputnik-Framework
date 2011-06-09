<?php

/*
	Implementation of RC4
    Make it ease to port to AES
*/
class Crypt {

	private $S = array(); // S vector
	private $key = "";

	private function SwapElem($i, $j) {
		$buffer = $this->S[$i];
		$this->S[$i] = $this->S[$j];
		$this->S[$j] = $buffer;
	}

	private function KeySchedule($key) {
		$this->S = range(0, 255);
		$key_length = strlen($key);

		$j = 0;
		for($i=0; $i<256; $i++) {
			$j = ($j + $this->S[$i] + ord($key[$i % $key_length])) % 256;
			$this->SwapElem($i, $j);
		}

	}
	

	public function SetKey($key) {
		$this->key = $key;
	}

	public function Encrypt($text, $key="", $encode=true) {
		if($key != "")
			$this->SetKey($key);

		$this->KeySchedule($this->key); // key schedule part
		$buffer = "";
		$j = $i = 0;
		$text_length = strlen($text);
		for($c = 0; $c<$text_length; $c++) {
			$i = ($i + 1) % 256;
			$j = ($j + $this->S[$i]) % 256;
			$this->SwapElem($i, $j);
			$key_char = chr($this->S[($this->S[$i] + $this->S[$j]) % 256]);
			$buffer .= $text[$c] ^ $key_char;
		}
		if($encode==true)
			return base64_encode($buffer);
		else
			return $buffer;
	}

	public function Decrypt($text, $key="") {
		$text = base64_decode($text);
		return $this->Encrypt($text, $key, false); // same as encrypt
	}
}
