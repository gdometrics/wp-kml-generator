<?php

require_once('functions/constants.php');
require_once('functions/wkgdb.class.php');

require_once('functions/admin-functions.php');

function chopExtension($filename = ''){
	return substr($filename, 0, strrpos($filename, '.'));
}

function getExtension($filename = ''){
	return substr($filename, strrpos($filename, '.')+1);
}