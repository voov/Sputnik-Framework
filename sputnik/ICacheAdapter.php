<?php

interface ICacheAdapter {
	public function Get($key);
	public function Set($key, $value);
	public function Remove($key);
	public function Clean();
}