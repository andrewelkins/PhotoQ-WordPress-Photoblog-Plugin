<?php

//convert backslashes (windows) to slashes
$cleanPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
define('PHOTOQ_PATH', $cleanPath.'/');

require_once('PHPUnit/Framework.php');
require_once('myMockPress.php');

require_once(dirname(__FILE__) . '/../classes/PhotoQClassLoader.php');

class PhotoQClassNamingConventionTest extends PHPUnit_Framework_TestCase {
  	
	private $_conv;
	
	function setUp() {
		$this->_conv = new PhotoQClassNamingConvention('classes/', 'PhotoQ_', '_');
  	}
  	
	function testSimpleClass() {
 		$in = 'SomeClassName';
 		$expOut = 'classes/SomeClassName.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testSimplePhotoQ() {
 		$in = 'PhotoQ';
 		$expOut = 'classes/PhotoQ.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testSubdir() {
 		$in = 'Some_Name';
 		$expOut = 'classes/some/Some_Name.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testMultiSubdir() {
 		$in = 'Some_Name_Deep_Down';
 		$expOut = 'classes/some/name/deep/Some_Name_Deep_Down.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testPhotoQTopLevelClass() {
 		$in = 'PhotoQTopLevelClass';
 		$expOut = 'classes/PhotoQTopLevelClass.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testPhotoQLowLevelClass() {
 		$in = 'PhotoQ_Error_ErrorHandler';
 		$expOut = 'classes/error/PhotoQ_Error_ErrorHandler.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testPhotoQLowLevelPhotoQClass() {
 		$in = 'PhotoQ_Error_PhotoQClass';
 		$expOut = 'classes/error/PhotoQ_Error_PhotoQClass.php';
    	$this->assertEquals($expOut, $this->_conv->getPath($in));
  	}
  	
	function testValid() {
 		$in = 'PhotoQ_Error_ErrorHandler';
 		$this->assertTrue($this->_conv->isValid($in));
  	}
  	
	function testValidTopLevel() {
 		$in = 'PhotoQ';
 		$this->assertTrue($this->_conv->isValid($in));
  	}
  		
	function testInvalid() {
 		$in = 'Error_PhotoQClass';
 		$this->assertFalse($this->_conv->isValid($in));
  	}

  	
}
