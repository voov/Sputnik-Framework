<?php


function traverse_path_recursive($start_path, &$path, $uri_array, $index = 0) {
	$dir = $start_path . implode("/", $path) . "/" . $uri_array[$index];
	if (is_dir($dir)) {
		// Add to the class path
		$path[] = $uri_array[$index];
		// If the URI is longer
		if (count($uri_array) > $index) {
			traverse_path_recursive($start_path, $path, $uri_array, $index + 1); // Go one further
		}
	}
}

function traverse_path($start_path, $uri_array, $index = 0) {
	$path = array();
	traverse_path_recursive($start_path, $path, $uri_array, $index);
	error_log($start_path);
	return $path;
}
