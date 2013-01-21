<?php
if(!defined('ABSPATH')){
	define( 'ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/' );
}


if(!defined('PHOTOQ_PATH')){
	//convert backslashes (windows) to slashes
	$cleanPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
	define('PHOTOQ_PATH', $cleanPath.'/');
}

require_once('PHPUnit/Framework.php');
require_once('myMockPress.php');

require_once(dirname(__FILE__) . '/../classes/PhotoQClassLoader.php');