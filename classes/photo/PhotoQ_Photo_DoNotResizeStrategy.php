<?php

class PhotoQ_Photo_DoNotResizeStrategy extends PhotoQ_Photo_ResizeStrategy
{
	
	
	public function getScaledWidth()
	{
		return $this->_originalDimension->getWidth();
	}
	
	public function getScaledHeight()
	{
		return $this->_originalDimension->getHeight();
	}
	
	public function widthCounts()
	{
		return true;
	}
		
	
}
