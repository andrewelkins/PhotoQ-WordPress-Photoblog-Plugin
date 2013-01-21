<?php
class PhotoQ_File_ServerCopier extends PhotoQ_File_Importer
{
	
	private $_sourcePath;
	
	public function __construct($destinationDir, $sourcePath){
		$this->_sourcePath = $sourcePath;
		parent::__construct($destinationDir);
	}
	
	public function import(){
		$newPath = $this->getDestinationDir() . '/' . basename($this->_sourcePath);
		//move file if we have permissions, otherwise copy file
		//suppress warnings if original could not be deleted due to missing permissions
		if(!@PhotoQHelper::moveFileIfNotExists(new PhotoQ_File_SourceDestinationPair($this->_sourcePath, $newPath))){
			$errMsg = sprintf(__('Unable to move %1$s to %2$s', 'PhotoQ'), $this->_sourcePath, $newPath);
			add_settings_error('wimpq-photoq', 'upload-move-error',
					sprintf(__('The file upload failed with the following error: %s', 'PhotoQ'), $errMsg), 'error');
			
			return false;
		}
		return $newPath;
	}
	
	
}