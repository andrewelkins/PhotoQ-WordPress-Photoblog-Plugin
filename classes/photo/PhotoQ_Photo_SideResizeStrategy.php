<?php
class PhotoQ_Photo_SideResizeStrategy extends PhotoQ_Photo_ResizeStrategy
{
	
	private $_constrSide;
	

	public function __construct($name, PhotoQ_Option_OptionController $oc, 
		PhotoQ_Photo_Dimension $originalDimension)
	{
		parent::__construct($oc, $originalDimension);
		$this->_constrSide = $this->_oc->getValue($name.'-imgSide');
	}
	
	public function widthCounts()
	{
		return $this->_originalDimension->getRatio() < 1;
	}
	
	public function getScaledWidth()
	{
		if($this->_originalDimension->getRatio() >= 1)
			return min($this->_originalDimension->getWidth(), round($this->_constrSide*$this->_originalDimension->getRatio()));
		else
			return min($this->_originalDimension->getWidth(), $this->_constrSide);
	}
	
	public function getScaledHeight()
	{
		if($this->_originalDimension->getRatio() >= 1)
			return min($this->_originalDimension->getHeight(), $this->_constrSide);
		else
			return min($this->_originalDimension->getHeight(), round($this->_constrSide/$this->_originalDimension->getRatio()));
	}
		
	
}
