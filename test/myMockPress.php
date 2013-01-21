<?php
/**
 * This file mocks out wordpress functions such that they can be used in unit tests.
 * We try to make use of mockpress whenever possible. below we just define some functions 
 * that are not yet supported by mockpress.
 */
require_once('MockPress/mockpress.php');


function site_url($path = '', $scheme = null) {
	return get_option('siteurl');
}

function is_multisite(){
	return false;
}

function _x( $text, $domain = 'default' ) {
	return $text;
}