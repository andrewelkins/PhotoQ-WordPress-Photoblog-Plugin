<?php
/**
 * Converts a pre 2.0 post to a 2.0 one.
 * @author manu
 *
 */
class PhotoQ_Util_UpgradePreTwoZeroQueueDB {

	/**
	 * the entire database structure for queued posts changed in 1.9.5
	 * do the necessary conversions here
	 */
	public function upgrade(){
		$oldEntries = $this->_getQueueEntriesToConvert();
		foreach($oldEntries as $entry)
			$this->_convertEntry($entry);
		
		$this->_dropUnusedDBElements();
		
	}
	
	private function _getQueueEntriesToConvert(){
		global $wpdb;
		return $wpdb->get_results(
			'SELECT * FROM '. $wpdb->prefix . "photoq WHERE q_fk_post_id = 0 ORDER BY q_position");
	}
	
	private function _convertEntry($entry){
		if(!$customID = $this->_insertCustomPostType($entry))
			return false;
			
		wp_set_post_categories($customID, $this->_getCats($entry));
		$this->_insertPostFieldMeta($customID, $entry);
		$this->_registerInNewQueue($entry, $customID);
	}
	
	private function _getCats($entry){
		global $wpdb;
		return $wpdb->get_col(
			'SELECT category_id FROM '. $wpdb->prefix . "photoq2cat WHERE q_fk_img_id = " . $entry->q_img_id);
	}
	
	private function _getFieldVal($entry, $field){
		global $wpdb;
		return $wpdb->get_var(
			'SELECT q_field_value FROM '. $wpdb->prefix . "photoqmeta WHERE q_fk_img_id = " . $entry->q_img_id . " AND q_fk_field_id = " . $field->q_field_id);
	}
	
	private function _insertCustomPostType($entry){
		$postData = $this->_generatePostData($entry);
		if ($postID = wp_insert_post($postData)) {
			add_post_meta($postID, PhotoQ_Photo_Photo::EXIF_FULL_FIELD_NAME, $entry->_exif, true);		
		}
		return $postID;
	}
	
	private function _generatePostData($entry){
		$postData = array(
			'post_author' => $entry->q_fk_author_id,
			'post_status' => 'private',
			'post_type' => PhotoQQueuedPostType::POST_TYPE_NAME,
			'post_title' => $entry->q_title,
			'post_date' => $entry->q_date,
			'post_name' =>  $entry->q_slug,
			'tags_input' =>  $entry->q_tags,
			'post_content' => $entry->q_descr,
			'post_excerpt' => $entry->q_imgname,
		);
		//to safely insert values into db
		return add_magic_quotes($postData);	
	}
	
	private function _insertPostFieldMeta($postID, $entry){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fields = $fieldTable->getAllFields();
		$fieldValue = '';
		if($fields){
			foreach ($fields as $field) {
				add_post_meta($postID, $field->q_field_name, $this->_getFieldVal($entry, $field));
			}
		}
	}
	
	private function _registerInNewQueue($entry, $customID){
		//add photo to queue
		$qTable = new PhotoQ_DB_QueueTable();
		$qTable->setPostID($entry->q_img_id, $customID);
	}
	
	private function _dropUnusedDBElements(){
		global $wpdb;
				
		$wpdb->query("DROP TABLE IF EXISTS ". $wpdb->prefix . "photoq2cat");
		$wpdb->query("DROP TABLE IF EXISTS ". $wpdb->prefix . "photoqmeta");
		$wpdb->query("ALTER TABLE ". $wpdb->prefix . "photoq DROP column q_title, DROP column q_imgname, DROP column q_slug, DROP column q_descr, DROP column q_tags, DROP column q_exif, DROP column q_date, DROP column q_fk_author_id");
	}

}