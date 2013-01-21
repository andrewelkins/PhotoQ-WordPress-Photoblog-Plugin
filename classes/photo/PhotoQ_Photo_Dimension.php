<?php
/**
 * Simple value object to encapsulate width and height of an image.
 */
class PhotoQ_Photo_Dimension{
	
	private $_width;
	private $_height;
	
	public function __construct($width, $height){
		
		if(!$this->_areArgumentsValid($width, $height))
			throw new InvalidArgumentException(
				__('Width and height of Dimension must be positive and numeric.', 'PhotoQ')
			);
			
		$this->_width = intval($width);
		$this->_height = intval($height);
		
	}
	
	private function _areArgumentsValid($width, $height){
		return $this->_areArgumentsNumeric($width, $height) && $this->_areArgumentsPositive($width, $height);
	}
	
	private function _areArgumentsNumeric($width, $height){
		return is_numeric($width) && is_numeric($height);
	}
	
	private function _areArgumentsPositive($width, $height){
		return $width > 0 && $height > 0;
	}
	
	public function getWidth(){
		return $this->_width;
	}
	
	public function getHeight(){
		return $this->_height;
	}
	
	public function getRatio(){
		return $this->_width/$this->_height;
	}
}