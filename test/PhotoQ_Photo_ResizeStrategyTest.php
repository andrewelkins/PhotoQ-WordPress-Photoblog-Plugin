<?php

require_once('test-header.php');

class PhotoQ_Photo_ResizeStrategyTest extends PHPUnit_Framework_TestCase {
  	
	private $_imgSizeName;
	
	private $_configArray;
	private $_oc;
	
	public function setUp(){
		
		$this->_imgSizeName = 'thumbnail';
		$this->_initConfigArray();
		$this->_initOptionControllerMockObject();
	}
	
	private function _initConfigArray(){
		$this->_configArray = array(
    		$this->_imgSizeName.'-zoomCrop' => false,
    		$this->_imgSizeName.'-imgWidth' => 100,
    		$this->_imgSizeName.'-imgHeight' => 200,
    	);
	}
	
	private function _initOptionControllerMockObject(){
		$this->_oc = $this->getMock('PhotoQ_Option_OptionController', array(), array(), '', false);
        $this->_oc->expects($this->any())
             ->method('getValue')
             ->will($this->returnCallback(array($this, 'config')));
	}
    public function config($key){
    	return $this->_configArray[$key];
    }
    
    
	
	public static function rectProviderNoCrop()
    {
    	$portrait = new PhotoQ_Photo_Dimension(500, 1000);
    	$landscape = new PhotoQ_Photo_Dimension(1000, 500);
        return array(
          array($portrait, 100, 200, 100, 200, true),
          array($portrait, 200, 100, 50, 100, false),
          array($landscape, 100, 200, 100, 50, true),
          array($landscape, 200, 100, 200, 100, true),
        );
    }
    
	public static function rectProviderCrop()
    {
    	$portrait = new PhotoQ_Photo_Dimension(500, 1000);
    	$landscape = new PhotoQ_Photo_Dimension(1000, 500);
        return array(
          array($portrait, 100, 200, 100, 200, true),
          array($portrait, 200, 100, 200, 100, false),
          array($landscape, 100, 200, 100, 200, true),
          array($landscape, 200, 100, 200, 100, true),
        );
    }
    
  	
	public function testRectStrategyShouldCrop(){
		$rs = new PhotoQ_Photo_RectResizeStrategy($this->_imgSizeName, $this->_oc, new PhotoQ_Photo_Dimension(500, 1000));
		$this->assertEquals(false, $rs->shouldCrop());
		$this->_configArray[$this->_imgSizeName.'-zoomCrop'] = true;
		$this->assertEquals(false, $rs->shouldCrop());
	}

	/**
     * @dataProvider rectProviderNoCrop
     */
	public function testRectStrategyNoCrop($originalDimension, $constrWidth, $constrHeight, $scaledWidth, $scaledHeight, $widthCounts){
		$this->_configArray[$this->_imgSizeName.'-imgWidth'] = $constrWidth;
		$this->_configArray[$this->_imgSizeName.'-imgHeight'] = $constrHeight;
		$rs = new PhotoQ_Photo_RectResizeStrategy($this->_imgSizeName, $this->_oc, $originalDimension);
		$this->assertEquals($scaledWidth, $rs->getScaledWidth());
		$this->assertEquals($scaledHeight, $rs->getScaledHeight());
		$this->assertEquals($widthCounts, $rs->widthCounts());
	}
	
	/**
     * @dataProvider rectProviderCrop
     */
	public function testRectStrategyCrop($originalDimension, $constrWidth, $constrHeight, $scaledWidth, $scaledHeight, $widthCounts){
		$this->_configArray[$this->_imgSizeName.'-imgWidth'] = $constrWidth;
		$this->_configArray[$this->_imgSizeName.'-imgHeight'] = $constrHeight;
		$this->_configArray[$this->_imgSizeName.'-zoomCrop'] = true;
		$rs = new PhotoQ_Photo_RectResizeStrategy($this->_imgSizeName, $this->_oc, $originalDimension);
		$this->assertEquals($scaledWidth, $rs->getScaledWidth());
		$this->assertEquals($scaledHeight, $rs->getScaledHeight());
		$this->assertEquals($widthCounts, $rs->widthCounts());
	}
	

}
