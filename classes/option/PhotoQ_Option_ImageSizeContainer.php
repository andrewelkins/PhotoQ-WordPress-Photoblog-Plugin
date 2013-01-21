<?php
class PhotoQ_Option_ImageSizeContainer extends RO_Option_ExpandableComposite
{
	
	/**
	 * Returns an array containing names of imagesizes that have a watermark.
	 * @return array
	 */
	function getImageSizeNamesWithWatermark(){
		return $this->getChildrenNamesWithAttribute('hasWatermark');
	}
	
	
}
