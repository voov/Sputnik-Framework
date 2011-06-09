<?php
function http_parse_headers($header) {
	$retVal = array();
	$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
	foreach ($fields as $field) {
		if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
			$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
			if (isset($retVal[$match[1]])) {
				$retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
			} else {
				$retVal[$match[1]] = trim($match[2]);
			}
		}
	}
	return $retVal;
}
