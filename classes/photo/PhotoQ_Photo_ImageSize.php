<?php

class PhotoQ_Photo_ImageSize
{
	
	protected $_name;
	protected $_imgName;
	
	
	protected $_oc;
	private $_quality;
	private $_hasWatermark;
	private $_writeIPTC;

	protected $_location;
	private $_resizeStrategy;
	
	protected function __construct($name, $imgName, 
		PhotoQ_Photo_ImageSizeLocation $location, 
		PhotoQ_Photo_ResizeStrategy $resizeStrategy
	)
	{
		$this->_name = $name;
		$this->_imgName = $imgName;
		$this->_location = $location;
		$this->_resizeStrategy = $resizeStrategy;
		
		
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
		
		if(!is_a($this, 'PhotoQ_Photo_OriginalImageSize')){ 
			$this->_quality = $this->_oc->getValue($this->_name . '-imgQuality');
			$this->_hasWatermark = $this->_oc->getValue($this->_name.'-watermark');
			$this->_writeIPTC = $this->_oc->getValue($this->_name.'-writeIPTC');
		}
	}
	
	
	/**
	 * Use this one (factory pattern) to create instances of this class. 
	 *
	 * @param unknown_type $name
	 */
	public static function createInstance($name, $imgName, PhotoQ_Photo_ImageSizeLocation $location, PhotoQ_Photo_Dimension $originalDimension)
	{
		$oc = PhotoQ_Option_OptionController::getInstance();
		if($name == PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER){
			return new PhotoQ_Photo_OriginalImageSize($name, $imgName, $location, 
				new PhotoQ_Photo_DoNotResizeStrategy($oc, $originalDimension));
		}else{
			switch($oc->getValue($name.'-imgConstraint')){
				case 'rect':
					$strategy = new PhotoQ_Photo_RectResizeStrategy($name, $oc, $originalDimension);
					break;
				case 'side':
					$strategy = new PhotoQ_Photo_SideResizeStrategy($name, $oc, $originalDimension);
					break;
				case 'fixed':
					$strategy = new PhotoQ_Photo_FixedResizeStrategy($name, $oc, $originalDimension);
					break;
				case 'noResize':
					$strategy = new PhotoQ_Photo_DoNotResizeStrategy($oc, $originalDimension);
					break;
			}
			return new PhotoQ_Photo_ImageSize($name, $imgName, $location, $strategy);
		}
	}
	
	
	public function getName()
	{
		return $this->_name;
	}
	
	public function makeFilenameUnique(){
		$this->_location->makeFilenameUnique();
	}
	
	
	
	public function createPhoto($oldOriginalPath){
		PhotoQHelper::debug('enter createPhoto()');
		//create the needed year-month-directory
		if(!PhotoQHelper::createDir($this->_location->getYearMonthDirPath()))
			throw new PhotoQ_Error_Exception(sprintf(__('Error when creating directory: %s.', 'PhotoQ'),$this->_location->getYearMonthDirPath()));
		if($this->_resizeStrategy->shouldCrop())//constr. width and height decide
			$this->_createThumb($oldOriginalPath, $this->_location->getPath(), $this->_resizeStrategy->getScaledWidth(), $this->_resizeStrategy->getScaledHeight());
		else
			if($this->_resizeStrategy->widthCounts()) //it is the width that counts
				$this->_createThumb($oldOriginalPath, $this->_location->getPath(), $this->_resizeStrategy->getScaledWidth());
			else //it is height
				$this->_createThumb($oldOriginalPath, $this->_location->getPath(), 0, $this->_resizeStrategy->getScaledHeight());
		
		if($this->_writeIPTC)
			PhotoQExif::addIPTCInfo($oldOriginalPath, $this->_location->getPath());
	}
	
	public function deleteResizedPhoto()
	{
		if(file_exists($this->_location->getPath()))
			unlink($this->_location->getPath());
	}
	
	protected function _createThumb($inFile, $outFile, $width = 0, $height = 0)
	{
		PhotoQHelper::debug('enter _createThumb() ' . $this->getName());
		require_once(PHOTOQ_PATH.'lib/phpThumb_1.7.9x/phpthumb.class.php');
		// create phpThumb object
		$phpThumb = new phpThumb();
		//set imagemagick path here
		$phpThumb->config_imagemagick_path = 
			( $this->_oc->getValue('imagemagickPath') ? $this->_oc->getValue('imagemagickPath') : null );
		
		// set data source -- do this first, any settings must be made AFTER this call
		$phpThumb->setSourceFilename($inFile);
		
		// PLEASE NOTE:
		// You must set any relevant config settings here. The phpThumb
		// object mode does NOT pull any settings from phpThumb.config.php
		//$phpThumb->setParameter('config_document_root', '/home/groups/p/ph/phpthumb/htdocs/');
		$phpThumb->setParameter('config_temp_directory', $this->_oc->getCacheDir());
		
		// set parameters (see "URL Parameters" in phpthumb.readme.txt)
		if($height)
			$phpThumb->setParameter('h', $height);
		if($width)
			$phpThumb->setParameter('w', $width);
			
		$phpThumb->setParameter('q', $this->_quality);
		
		//rect images may be cropped to the exact size
		if($this->_resizeStrategy->shouldCrop())
			$phpThumb->setParameter('zc', 'C');
		
		//$phpThumb->setParameter('fltr', 'gam|1.2');
		if($this->_hasWatermark && $wmPath = get_option('wimpq_watermark')){
			$phpThumb->setParameter('fltr', 
			'wmi|'.$wmPath.'|'.
			$this->_oc->getValue('watermarkPosition').'|'.
			$this->_oc->getValue('watermarkOpacity').'|'.
			$this->_oc->getValue('watermarkXMargin').'|'.
			$this->_oc->getValue('watermarkYMargin'));
		}
		PhotoQHelper::debug('generating thumb...');
		// generate & output thumbnail
		if ($phpThumb->GenerateThumbnail()) { 
			if (!$phpThumb->RenderToFile($outFile)) {
				throw new PhotoQ_Error_PhpThumbException(
					$phpThumb->debugmessages);
			}
		}else{
			throw new PhotoQ_Error_PhpThumbException(
				$phpThumb->debugmessages, $phpThumb->fatalerror);
		}
	}
	
	public function getThisPathFromOriginalPath($originalPath){
		return $this->_location->getThisPathFromOriginalPath($originalPath);
	}

	public function getYearMonthDirPath()
	{
		return $this->_location->getYearMonthDirPath();
	}

	public function getPath()
	{
		return $this->_location->getPath();
	}

	public function getUrl()
	{
		return $this->_location->getUrl();
	}
	
	public function getScaledWidth()
	{
		return $this->_resizeStrategy->getScaledWidth();
	}
	
	public function getScaledHeight()
	{
		return $this->_resizeStrategy->getScaledHeight();
	}
	
}
