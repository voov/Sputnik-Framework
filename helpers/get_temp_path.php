<?php

function get_temp_path() {

	if (function_exists("sys_get_temp_dir")) return sys_get_temp_dir();

	if ($temp = getenv('TMP')) return $temp;
	if ($temp = getenv('TEMP')) return $temp;
	if ($temp = getenv('TMPDIR')) return $temp;
	$temp = tempnam(__FILE__, '');
	if (file_exists($temp)) {
		unlink($temp);
		return dirname($temp);
	}
	return null;
}
