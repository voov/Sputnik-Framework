<?php

function valid_email($str) {
	if (preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $str)) {
		return true;
	} else {
		FormValidator::SetMessage("valid_email", "The field '<b>%s</b>' must be a valid email!");
		return false;
	}
}

function equals($str, $to) {
	if(strcmp($str, $to) == 0)
		return true;
	else {
		FormValidator::SetMessage("equals", "The field '<b>%s</b>' does not equal to '$to'");
		return false;
	}
}

function is_checked($str) {
    if($str == "1") {
        return true;
    } else {
        FormValidator::SetMessage("is_checked", "The field '<b>%s</b>' is not checked");
        return false;
    }
}

?>
