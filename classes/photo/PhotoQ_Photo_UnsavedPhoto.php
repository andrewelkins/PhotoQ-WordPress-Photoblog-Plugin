<?php
class PhotoQ_Photo_UnsavedPhoto
{
	private $_importStrategy;
	
	private $_title;
	private $_description = '';
	private $_tags;
	private $_slug = '';
	private $_exif;
	private $_filename;
	
	private $_oc;
	
	public function __construct(PhotoQ_File_Importer $importStrategy, $title, $tags){
		$this->_importStrategy = $importStrategy;
		$this->_title = $title;
		$this->_tags = $tags;
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
	}
	
	//uploads a photo, creates thumbnail and puts it to the end of the queue
	function saveToQueue()
	{	
		if (!$path = $this->_importStrategy->import())
			return false;
				
		$this->_extractExifInfo($path);
	
	    $this->_filename = basename($path);
		
		$this->_setTitle();

		if(!$customID = $this->_insertCustomPostType())
			return false;
			
		if(!$this->_addToQueue($customID))
			return false;

		$taxonomies = new PhotoQ_Util_Taxonomies();
		$taxonomies->updatePostTaxonomies($customID);
		
		$this->_insertPostFieldMeta($customID);
		
		$queue = PhotoQQueue::getInstance();
		add_settings_error('wimpq-photoq', 'upload-successful',
			sprintf(
				_x('Successfully uploaded. \'%1$s\' added to queue at position %2$d.', 'PhotoQ'), 
				$this->_filename, 
				$queue->getLength() + 1
			), 
			'updated'
		);
			
		return true;
	}
	
	private function _extractExifInfo($path){
		//get exif meta data
		$this->_exif = PhotoQExif::readExif($path);
		
		PhotoQHelper::debug('saveToQueue: exif read');
		
		$exifDescr = $this->_exif['ImageDescription'];
		
		// use EXIF image description if none was provided
		if ($this->_oc->getValue('descrFromExif'))
			$this->_description = $exifDescr;
			
		if(!empty($exifDescr) && $this->_oc->getValue('autoTitleFromExif'))
			$this->_title = $exifDescr;
		
		//add IPTC keywords to default tags
		$this->_tags .= $this->_exif['Keywords'];		
	}
	
	private function _setTitle(){
		//make nicer titles
		$titleGenerator = new PhotoQTitleGenerator(
			$this->_oc->getValue('autoTitleRegex'), 
			$this->_oc->getValue('autoTitleNoCaps'), 
			$this->_oc->getValue('autoTitleNoCapsShortWords'),
			$this->_oc->getValue('autoTitleCaps')
		);
		$this->_title = $titleGenerator->generateAutoTitleFromFilename($this->_title);
	}
	
	private function _addToQueue($customID){
		$queue = PhotoQQueue::getInstance();
		//add photo to queue
		$qTable = new PhotoQ_DB_QueueTable();
		return $qTable->insertQueueEntry($queue->getLength(), $customID);
	}
	
	private function _insertCustomPostType(){
		$postData = $this->_generatePostData();
		if ($postID = wp_insert_post($postData)) {
			add_post_meta($postID, PhotoQ_Photo_Photo::EXIF_FULL_FIELD_NAME, $this->_exif, true);		
		}
		return $postID;
	}
	
	private function _insertPostFieldMeta($postID){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fields = $fieldTable->getAllFields();
		$fieldValue = '';
		if($fields){
			foreach ($fields as $field) {
				//the common info box for ftp uploads submits an array we don't want to use here
				if(!is_array($_POST["$field->q_field_name"]))
					$fieldValue = $_POST["$field->q_field_name"];
				add_post_meta($postID, $field->q_field_name, $fieldValue);
			}
		}
	}
	
	private function _getValidDateTime(){	
		$dateTime = $this->_exif['DateTimeOriginal'];
		if ( empty($this->_exif['DateTimeOriginal']) || '0000:00:00 00:00:00' == $dateTime){
			$dateTime = current_time('mysql');
		}
		return $dateTime;
	}
	
	private function _getPostAuthor(){
		global $user_ID;	
		if(empty($user_ID))
			return $this->_oc->getValue('qPostAuthor');
		else
			return $user_ID;
	}
	
	private function _generatePostData(){
		$postData = array(
			'post_author' => $this->_getPostAuthor(),
			'post_status' => 'private',
			'post_type' => PhotoQQueuedPostType::POST_TYPE_NAME,
			'post_title' => $this->_title,
			'post_date' => $this->_getValidDateTime(),
			'post_name' =>  $this->_slug,
			'tags_input' =>  $this->_tags,
			'post_category' => array($this->_oc->getValue('qPostDefaultCat')),
			'post_content' => $this->_description,
			'post_excerpt' => $this->_filename,
		);
		//to safely insert values into db
		return add_magic_quotes($postData);	
	}
	
}