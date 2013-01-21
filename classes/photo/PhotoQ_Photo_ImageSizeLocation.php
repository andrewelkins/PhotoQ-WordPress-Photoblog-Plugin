<?php
class PhotoQ_Photo_ImageSizeLocation
{
	
	private $_yearMonthDir;
	private $_dirPath;
	private $_yearMonthDirPath;
	private $_path;
	private $_filename;
	private $_oc;

	public function __construct($imgSizeDirName, $imgName, $yearMonthDir){
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
		
		$this->_filename = $imgName;
		$this->_yearMonthDir = $yearMonthDir;
		$this->_dirPath = $this->_oc->getImgDir() . $imgSizeDirName . '/';
		$this->_yearMonthDirPath = $this->_dirPath . $this->_yearMonthDir;
		$this->_setPath();
		
	}
	
	private function _setPath(){
		$this->_path = $this->_yearMonthDirPath . $this->_filename;
	}
	
	public function getThisPathFromOriginalPath($originalPath){
		$imgDirs = new PhotoQ_File_ImageDirs();
		return preg_replace('#'.$this->_oc->getImgDir() . $imgDirs->getCurrentOriginalDirName(). '#', $this->_dirPath, $originalPath);
	}

	
	public function getYearMonthDirPath(){
		return $this->_yearMonthDirPath;
	}

	public function getPath(){
		return str_replace('\\', '/', $this->_path);
	}
	
	public function getFilename(){
		return $this->_filename;
	}
	
	public function makeFilenameUnique(){
			$this->_filename = wp_unique_filename($this->_yearMonthDirPath, $this->_filename);
			$this->_setPath();
	}

	public function getUrl(){
		return PhotoQHelper::getRelUrlFromPath($this->_path);
	}
}