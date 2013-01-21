<?php

require_once('PHPUnit/Framework.php');
require_once('myMockPress.php');

require_once(dirname(__FILE__) . '/../classes/photo/PhotoQ_Photo_Dimension.php');

class PhotoQ_Photo_DimensionTest extends PHPUnit_Framework_TestCase {
  	
	
	
	public static function provider()
    {
        return array(
          array(34, 45),
          array('12', '566'),
          array(34.6, 344.3),
          array('12.4', '3.8'),
        );
    }
  	
  	/**
     * @dataProvider provider
     */
	public function testGetters($width, $height){
 		$dim = new PhotoQ_Photo_Dimension($width, $height);
		$this->assertEquals(floor($width), $dim->getWidth());
		$this->assertEquals(floor($height), $dim->getHeight());
  	}

	public function testRatio() {
 		$dim = new PhotoQ_Photo_Dimension(34, 45);
		$this->assertEquals(34/45, $dim->getRatio());
  	}
  	
	public function testInfiniteRatio() {
		$this->setExpectedException('InvalidArgumentException');
 		$dim = new PhotoQ_Photo_Dimension(34, 0);
 		$dim->getRatio();
  	}
  	
  	
	public static function illegalProvider()
    {
        return array(
          array(0, 3),
          array('12', '0'),
          array(-2, -4),
          array('-12', '3'),
          array('asdf', 'asfd'),
        );
    }
  	
  	/**
     * @dataProvider illegalProvider
     */
  	public function testIllegalArguments($width, $height){
  		$this->setExpectedException('InvalidArgumentException');
  		$dim = new PhotoQ_Photo_Dimension($width, $height);
  	}
}
