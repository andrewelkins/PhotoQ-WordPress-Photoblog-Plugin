<?php

class PhotoQ_Photo_OriginalImageSize extends PhotoQ_Photo_ImageSize
{
	
	/**
	 * Overwrites default behavior. No call to phpThumb needed for original photo. 
	 * Just move it to the imgdir.
	 *
	 * @param unknown_type $oldOriginalPath
	 * @return unknown
	 */
	function createPhoto($oldOriginalPath){
		//create directory
		if(!PhotoQHelper::createDir($this->_location->getYearMonthDirPath()))
			throw new PhotoQ_Error_Exception(sprintf(
				_x('Error when creating directory: %s| dirname', 'PhotoQ'), 
					$this->_location->getYearMonthDirPath()));
		//move the image file
		$srcDest = new PhotoQ_File_SourceDestinationPair($oldOriginalPath, $this->_location->getPath());
		if (!$srcDest->destinationExists()) {
			if(!PhotoQHelper::moveFile($srcDest))
				throw new PhotoQ_Error_Exception(sprintf(
					_x('Unable to move %s, posting aborted.| imgname', 'PhotoQ'), $this->_imgName));
		}else{
			throw new PhotoQ_Error_Exception(sprintf(_x('Image %s already exists, posting aborted.| imgname', 'PhotoQ'), $this->_imgName));
		}
	}
	
	
	/**
	 * Never delete orginal file like a scaled one. Use special function destroyForever()
	 *
	 * @param unknown_type $imgName
	 * @param unknown_type $this->_yearMonthDir
	 */
	function deleteResizedPhoto()
	{
		return false;
	}
	
	
	/**
	 * We never create a thumb for the original image size.
	 */
	protected function _createThumb($inFile, $outFile, $width = 0, $height = 0)
	{
		throw new PhotoQ_Error_PhpThumbException(array(), __("Don't call createThumb() on original image.", 'PhotoQ'));	
	}
			
}