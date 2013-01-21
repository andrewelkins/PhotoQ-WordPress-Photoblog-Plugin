<?php
/*
 Plugin Name: PhotoQ
 Version: 2.0b6
 Plugin URI: http://www.whoismanu.com/blog/
 Description: Automates WordPress photo publishing.
 Author: M. Flury
 Author URI: http://www.whoismanu.com
 License: GPL3
 */

/*
 	Copyright (C) 2010  M. Flury

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */
if (!function_exists('setPhotoQPath')) { 
	function setPhotoQPath(){
		if(!defined('PHOTOQ_PATH')){
			//convert backslashes (windows) to slashes
			$cleanPath = str_replace('\\', '/', dirname(__FILE__));
			define('PHOTOQ_PATH', $cleanPath.'/');
			define('PHOTOQ_DIRNAME', basename(PHOTOQ_PATH));
		}
	}
	
	
	function shouldLoadPhotoQ(){
		return isPhotoQCustomRequest() || is_admin();
	}
	
	function isPhotoQCustomRequest(){
		return isset($_REQUEST['photoQHandler']);
	}
	
	function setPhotoQDebugLevel(){
		if (!defined('PHOTOQ_DEBUG_LEVEL')) {
			define('PHOTOQ_DEBUG_OFF', '0');
			define('PHOTOQ_SHOW_PHP_ERRORS', '1');
			define('PHOTOQ_LOG_MESSAGES', '2');
	
			define('PHOTOQ_DEBUG_LEVEL', PHOTOQ_DEBUG_OFF);
		}	
	}
	
	function setPhotoQPHPErrorDisplay(){
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL ^ E_NOTICE);
	}
	
	
	function autoloadPhotoQClasses(){
		require_once(PHOTOQ_PATH.'classes/PhotoQClassLoader.php');
	}
	
	function isPhotoQBatchUpload(){
		$photoQOptionController = PhotoQ_Option_OptionController::getInstance();
		return $photoQOptionController->getValue('enableBatchUploads') && 
			isset($_POST['batch_upload']);
	}
	
	/**
	 * Copied from wp-admin/async-upload.php
	 */
	function enableGetPostAuthenticationForFlashUploader(){
		// Flash often fails to send cookies with the POST or upload, so we need to pass it in GET or POST instead
		if ( is_ssl() && empty($_COOKIE[SECURE_AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']) )
			$_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
		elseif ( empty($_COOKIE[AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']) )
			$_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
		if ( empty($_COOKIE[LOGGED_IN_COOKIE]) && !empty($_REQUEST['logged_in_cookie']) )
			$_COOKIE[LOGGED_IN_COOKIE] = $_REQUEST['logged_in_cookie'];
	}
}

setPhotoQPath();
if(shouldLoadPhotoQ()){
	setPhotoQDebugLevel();
	if(PHOTOQ_DEBUG_LEVEL >= PHOTOQ_SHOW_PHP_ERRORS)
		setPhotoQPHPErrorDisplay();
	
	autoloadPhotoQClasses();

	$photoq = new PhotoQ();
	
	if(isPhotoQBatchUpload())
		enableGetPostAuthenticationForFlashUploader();	
}

//try to serve a PhotoQ generated intermediate size when a featured image is requested.
//essentially a second plugin that loads apart from PhotoQ since it is also needed 
//outside of the admin section.
require_once(PHOTOQ_PATH . 'classes/PhotoQFeaturedImageProvider.php');
$photoQFeaturedImageProvider = new PhotoQFeaturedImageProvider();

