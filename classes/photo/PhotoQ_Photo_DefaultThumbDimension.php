<?php
/**
 * Simple value object for default dimensions of PhotoQ images.
 */
class PhotoQ_Photo_DefaultThumbDimension extends PhotoQ_Photo_Dimension
{
	const WIDTH = 200;
	const HEIGHT = 90;
	
	public function __construct(){
		parent::__construct(self::WIDTH, self::HEIGHT);
	}
	
}