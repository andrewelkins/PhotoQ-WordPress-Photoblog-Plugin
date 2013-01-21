<?php
require_once('PHPUnit/Framework.php');
require_once('MockPress/mockpress.php');
require_once(dirname(__FILE__) . '/../classes/PhotoQTitleGenerator.php');



class PhotoQTitleGeneratorTest extends PHPUnit_Framework_TestCase {
  	
	private $_defaultGenerator;
	
	function setUp() {
    	$this->_defaultGenerator = new PhotoQTitleGenerator('', 
			'for, and, nor, but, yet, both, either, neither, the, for, with, from, because, after, when, although, while', 
    		2,'I'
		);
  	}
  	
	function testSingleWord() {
 		$in = 'test.jpg';
 		$expOut = 'Test';
    	$this->assertEquals($expOut, $this->_defaultGenerator->generateAutoTitleFromFilename($in));
  	}

 	function testMultipleWordsAllLowerCase() {
 		$in = 'and this is a photo i took because i had to.jpg';
 		$expOut = 'And This is a Photo I Took because I Had To';
    	$this->assertEquals($expOut, $this->_defaultGenerator->generateAutoTitleFromFilename($in));
  	}

  	function testToSpaces() {
 		$in = 'and this__is a photo-i took because i had to.txt';
 		$expOut = 'And This is a Photo I Took because I Had To';
    	$this->assertEquals($expOut, $this->_defaultGenerator->generateAutoTitleFromFilename($in));
  	}
  	
	function testCustomRegex() {
		$customGen = new PhotoQTitleGenerator('(and|this)', 
			'for, and, nor, but, yet, both, either, neither, the, for, with, from, because, after, when, although, while', 
    		2,'I'
		);
 		$in = 'and this is a photo i took because i had to.jpg';
 		$expOut = 'Is a Photo I Took because I Had To';
    	$this->assertEquals($expOut, $customGen->generateAutoTitleFromFilename($in));
  	}
  	
}
