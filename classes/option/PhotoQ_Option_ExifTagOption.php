<?php

class PhotoQ_Option_ExifTagOption extends RO_Option_Composite
{
	var $_exifExampleValue;
		
	function __construct($exifKey, $exifExampleValue)
	{
		parent::__construct($exifKey);
		$this->_exifExampleValue = $exifExampleValue;
			
		$this->addChild(
			new RO_Option_TextField(
				$exifKey.'-displayName',
				'',
				__('Display Name','PhotoQ').': ',
				'',
				'<br/>',
				'20')
		);
		
		//whether to use it for tagFromExif
		$this->addChild(
			new RO_Option_CheckBox(
				$exifKey.'-tag',
				'0', 
				__('Create post tags from EXIF data','PhotoQ').'', 
				'', 
				''
			)
		);
		
	}
	
	function getExifKey(){
		return $this->getName();
	}
	
	function getExifExampleValue(){
		return $this->_exifExampleValue;
	}

}

