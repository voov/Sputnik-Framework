<?php

include_once "sputnik/ICacheAdapter.php";
 
class ShmCache implements ICacheAdapter {

	private $key_cache = array();
	private $cache_timeout = 120; // 2 minutes by default

	public function __construct() {
		if(!function_exists("shmop_open")) {
			trigger_error("SHMOP is not compiled into the current PHP interpreter");
			exit; // we should exit always
		}
	}

	public function __destruct() {
		// close all opened caches
		// will not delete the caches!
		foreach($this->key_cache as $id) {
			shmop_close($id);
		}
	}

	private function GarbageCollect() {
		
	}

	private function GetFileName($key) {
		$uid = md5($key_name);
		$tmpname = Helper::GI()->get_temp_path() . "/" . $uid . ".tmp";
		return $tmpname;
	}

	private function GetKey($key_name) {

		if(array_key_exists($uid, $this->key_cache)) {
			// We already cached the key's ID
			return $this->key_cache[$uid];
		}

		$tmpname = $this->GetFileName($key_name);
		if(!is_file($tmpname)) touch($tmpname);
		$id = shmop_open(ftok($tmpname, "t"), "c", 0644, 1024);
		if(!$id) return false;

		$this->key_cache[$uid] = $id;
		return $id;
	}

	private function Touch($key) {
		$data = $this->GetRaw($key);
		$this->Set($key, $data["value"]); // resets the time
	}

	public function Clean() {
		// TODO: Implement Clean() method.
	}

	public function Remove($key) {
		$id = $this->GetKey($key);
		if(!$id) return false;

		if(!shmop_delete($id)) return false;
		shmop_close($id);

		// delete the temporary file
		$tmpfile = $this->GetFileName($key);
		unlink($tmpfile);

		return true;
	}

	public function Set($key, $value) {
		$id = $this->GetKey($key);
		if(!$id) return false; // no ID is found
		$data["value"] = $value;
		$data["time"] = time();
		$written = shmop_write($id, serialize($data), 0);
		if($written > 0) return $written; // if we have successfully written some data
		return false;
	}

	private function GetRaw($key) {
		$id = $this->GetKey($key);
		if(!$id) return false; // no ID is found

		$size = shmop_size($id); // read all the data, from key
		// $size always returns the currently allocated memory size, not the size
		// of the truly used up space
		$data = unserialize(shmop_read($id, 0, $size));

		if(empty($data)) return false; // check if data is empty, is yes, there are no data saved

		return $data;
	}

	public function Get($key) {

		if(($data = $this->GetRaw($key)) == false) return false;

		if(time() - $data["time"] > $this->cache_timeout) {
			// touch key
			return false; // data should be reset, if needed because the cache is too old
		}

		return $data["value"];
	}
}
