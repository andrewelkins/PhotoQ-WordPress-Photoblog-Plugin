<?php
class PhotoQ_File_ImageDirs
{
	const THUMB_IDENTIFIER = 'thumbnail';
	const MAIN_IDENTIFIER = 'main';
	const ORIGINAL_IDENTIFIER = 'original';
	const QUEUE_IDENTIFIER = 'qdir';
	const WATERMARK_IDENTIFIER = 'photoQWatermark';
	const PRESETS_IDENTIFIER = 'myPhotoQPresets';
	const ORIGINAL_IDENTIFIER_DB_NAME = 'wimpq_originalFolder';
	
	private $_currentOriginalDirName = self::ORIGINAL_IDENTIFIER;
	
	public function __construct(){
		//get alternative original identifier if available
		$originalID = get_option(self::ORIGINAL_IDENTIFIER_DB_NAME);
		if($originalID)
			$this->setCurrentOriginalDirName($originalID);	
	}
	
	public function getCurrentOriginalDirName(){
		return $this->_currentOriginalDirName;
	}
	
	public function setCurrentOriginalDirName($newName){
		$this->_currentOriginalDirName = $newName;
	}
	
	public function isOriginalHidden(){
		return $this->_currentOriginalDirName !== self::ORIGINAL_IDENTIFIER;
	}
	
	/**
	 * Change "original" folder name to a random string if desired.
	 *
	 */
	public function updateOriginalFolderName($imgDir, $hideOriginals){
		$newName = self::ORIGINAL_IDENTIFIER;
		if($hideOriginals){
			//generate a random name
			$newName .= substr(md5(rand()),0,8);
		}
		$this->setCurrentOriginalDirName($newName);	
		
		//update option plus get old name
		$oldName = get_option(self::ORIGINAL_IDENTIFIER_DB_NAME);
		if($oldName)
			update_option(self::ORIGINAL_IDENTIFIER_DB_NAME, $newName);
		else{
			$oldName = self::ORIGINAL_IDENTIFIER;
			add_option(self::ORIGINAL_IDENTIFIER_DB_NAME, $newName);
		}
		return new PhotoQ_File_SourceDestinationPair($imgDir.$oldName, $imgDir.$newName);
	}
	
	/**
	 * Get content of imgdir so we know what to move
	 *
	 * @return array
	 */
	public function getImgDirContent($oldImgDir, $includingOriginal = true)
	{
		//determine which folders we are allowed to move
		$allowedFolders  = array(self::QUEUE_IDENTIFIER, self::WATERMARK_IDENTIFIER, self::PRESETS_IDENTIFIER);
		if($includingOriginal){
			$allowedFolders[] = $this->getCurrentOriginalDirName();
		}
		//only thing allowed to be moved are folders related to photoq
		$oc = PhotoQ_Option_OptionController::getInstance();
		$allowedFolders = array_merge($allowedFolders, $oc->getImageSizeNames());
		for($i = 0; $i<count($allowedFolders); $i++)
			$allowedFolders[$i] = $oldImgDir . $allowedFolders[$i];
		
		//get all visible files from old img dir
		$match = '#^[^\.]#';//exclude hidden files starting with .
		$visibleFiles = PhotoQHelper::getMatchingDirContent($oldImgDir, $match);
		
		//folders that are in both array will be moved
		return array_intersect($allowedFolders, $visibleFiles);
	}
	
}