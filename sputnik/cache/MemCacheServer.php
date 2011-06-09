<?php

require_once "sputnik/ICacheAdapter.php";

class MemCacheServer implements ICacheAdapter {

	private $memcache_server = false;

	public function __construct() {
		if($this->memcache_server == false)
			$this->memcache_server = new Memcache();
		
		// TODO: read from config
		$this->memcache_server->addserver("127.0.0.1");
	}

	public function Clean() {
		$this->memcache_server->flush();
	}

	public function Remove($key) {
		$this->memcache_server->delete($key);
	}

	public function Set($key, $value) {
		$this->memcache_server->set($key, $value, 0, 60);
	}

	public function Get($key) {
		
		return $this->memcache_server->get($key);
	}
}
 
