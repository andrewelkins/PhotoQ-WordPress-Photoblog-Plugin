<?php
abstract class PhotoQ_Photo_ResizeStrategy
{
	protected $_originalDimension;
	protected $_oc;
	
	public function __construct(PhotoQ_Option_OptionController $oc, PhotoQ_Photo_Dimension $originalDimension)
	{
		$this->_originalDimension = $originalDimension;
		$this->_oc = $oc;;
	}
	
	abstract public function widthCounts();
	abstract public function getScaledWidth();
	abstract public function getScaledHeight();
	
	public function shouldCrop(){
		return false;
	}

}