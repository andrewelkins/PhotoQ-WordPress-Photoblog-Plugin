<?php

class PhotoQ_Photo_FixedResizeStrategy extends PhotoQ_Photo_ResizeStrategy
{
	
	private $_constrFixed;

	public function __construct($name, PhotoQ_Option_OptionController $oc, 
		PhotoQ_Photo_Dimension $originalDimension)
	{
		parent::__construct($oc, $originalDimension);
		$this->_constrFixed = $this->_oc->getValue($name.'-imgFixed');
	}
	
	public function widthCounts()
	{
		return $this->_originalDimension->getRatio() >= 1;
	}
	
	public function getScaledWidth()
	{
		if($this->_originalDimension->getRatio() >= 1)
			return min($this->_originalDimension->getWidth(), $this->_constrFixed);
		else
			return min($this->_originalDimension->getWidth(), round($this->_constrFixed*$this->_originalDimension->getRatio()*$this->_originalDimension->getRatio()));
	}
	
	public function getScaledHeight()
	{
		if($this->_originalDimension->getRatio() >= 1)
			return min($this->_originalDimension->getHeight(), round($this->_constrFixed/$this->_originalDimension->getRatio()));
		else
			return min($this->_originalDimension->getHeight(), round($this->_constrFixed*$this->_originalDimension->getRatio()));
	}
		
	
}
