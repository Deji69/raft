<?php

function strStartsWith($string, $substring, $caseInsensitive = false) {
	if ($caseInsensitive) {
		return strncasecmp($string, $substring, strlen($substring)) == 0;
	}
	return strncmp($string, $substring, strlen($substring)) === 0;
}
