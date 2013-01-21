<?php
class PhotoQ_File_Uploader extends PhotoQ_File_Importer
{
	
	/**
	 * Uploads a file to $this->_destinationDir
	 */
	public function import(){
		
		//prepare for upload -> set photoq upload dirs
		$this->_registerPhotoQUploadDirWithWordPress();
	
		//set the options that we override
		$overrides = array('action'=>'save');
		$overrides['test_form'] = false; //don't test the form, swfupload is not (yet) able to send additional post vars.
		$overrides['mimes'] = apply_filters('upload_mimes', 
			array (
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif' => 'image/gif',
				'png' => 'image/png',
				'bmp' => 'image/bmp',
				'tif|tiff' => 'image/tiff'
			)
		);
		
		//upload the thing
		$file = wp_handle_upload($_FILES['Filedata'], $overrides);

		//reset upload options
		remove_filter( 'upload_dir', array($this, 'filterPhotoQUploadDir') );
		
		//check for errors
		if ( isset($file['error']) ){
			add_settings_error('wimpq-photoq', 'upload-error',
					sprintf(__('The file upload failed with the following error: %s', 'PhotoQ'), $file['error']), 'error');
			return false;
		}
		
		return $file['file'];
	}
	
	private function _registerPhotoQUploadDirWithWordPress(){
		add_filter( 'upload_dir', array($this, 'filterPhotoQUploadDir') );
	}
	
	/**
	 * Called before a file is uploaded. We replace the standard WP upload location 
	 * with the one we set in $this->_destinationDir before calling the upload function.
	 * @param $uploads
	 * @return unknown_type
	 */
	public function filterPhotoQUploadDir($uploads) {	
		$uploads = array(
			'path' 		=> $this->getDestinationDir(), 
			'url' 		=> PhotoQHelper::getRelUrlFromPath($this->getDestinationDir()), 
			'subdir' 	=> '', 
			'basedir' 	=> $this->getDestinationDir(), 
			'baseurl' 	=> PhotoQHelper::getRelUrlFromPath($this->getDestinationDir()), 
			'error' 	=> false 
		);
		
		// Make sure we have an uploads dir
		if (!wp_mkdir_p($uploads['path'])){
			$uploads['error'] = sprintf(
				__( 'Unable to create directory %s. Is its parent directory writable by the server?' ), 
				$uploads['path']
			);
		}
		
		return $uploads;
	}
	
}