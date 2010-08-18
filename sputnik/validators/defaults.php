<?php

function valid_email($str) {
	if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $str)) {
		return true;
	} else {
		FormValidator::SetMessage("valid_email", "The field '%s' must be a valid email!");
		return false;
	}
}
?>
