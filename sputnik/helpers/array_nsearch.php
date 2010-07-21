<?php

function array_nsearch($needle, array $haystack) {
	$it = new IteratorIterator(new ArrayIterator($haystack));
	foreach($it as $key => $val) {
		if(strcasecmp($val,$needle) === 0) {
			return $key;
		}
	}
	return false;
}


?>
