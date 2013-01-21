<?php
class PhotoQ_Photo_RectResizeStrategy extends PhotoQ_Photo_ResizeStrategy
{
	
	private $_constrDimension;
	private $_crop = false;
	
	public function __construct($name, PhotoQ_Option_OptionController $oc, 
		PhotoQ_Photo_Dimension $originalDimension)
	{
		parent::__construct($oc, $originalDimension);
		
		$this->_constrDimension = new PhotoQ_Photo_Dimension(
			$this->_oc->getValue($name.'-imgWidth'), $this->_oc->getValue($name.'-imgHeight')
		);
		
		//only crop rect images
		$this->_crop = $this->_oc->getValue($name.'-zoomCrop');
		
	}
	
	public function shouldCrop(){
		return $this->_crop;
	}
	
	public function widthCounts()
	{
		return $this->_originalDimension->getRatio() >= $this->_constrDimension->getRatio();
	}
	
	
	public function getScaledWidth()
	{
		if($this->_crop)
			return min($this->_originalDimension->getWidth(), $this->_constrDimension->getWidth());
		else
			if($this->_originalDimension->getRatio() >= $this->_constrDimension->getRatio())
				return min($this->_originalDimension->getWidth(), $this->_constrDimension->getWidth());	
			else
				return min($this->_originalDimension->getWidth(), round($this->_constrDimension->getHeight()*$this->_originalDimension->getRatio()));
	}
	
	public function getScaledHeight()
	{
		if($this->_crop)
			return min($this->_originalDimension->getHeight(), $this->_constrDimension->getHeight());
		else
			if($this->_originalDimension->getRatio() >= $this->_constrDimension->getRatio())
				return min($this->_originalDimension->getHeight(), round($this->_constrDimension->getWidth()/$this->_originalDimension->getRatio()));
			else
				return min($this->_originalDimension->getHeight(), $this->_constrDimension->getHeight());
	}
	
}
