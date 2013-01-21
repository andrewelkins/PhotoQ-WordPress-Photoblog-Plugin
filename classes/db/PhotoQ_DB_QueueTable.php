<?php
/**
 * Provides CRUD functionality for PhotoQ Queue DB table.
 * @author flury
 *
 */
class PhotoQ_DB_QueueTable extends PhotoQ_DB_Table
{
	
	const NAME = 'photoq';
	
	public function __construct(){
		parent::__construct(self::NAME);
	}
	
	protected function _getFieldDefinition(){
		return "(
			q_img_id bigint(20) NOT NULL AUTO_INCREMENT,
			q_position int(10) NOT NULL default '0',
			q_edited tinyint default 0,
			q_fk_post_id bigint(20) unsigned NOT NULL default '0',
			PRIMARY KEY  (q_img_id)
		)";
	}
	
	public function insertQueueEntry($qLength, $customPostID){
		if(!$this->_wpdb->query("INSERT INTO ".$this->getFullName()." 
			(q_position, q_fk_post_id) 
			VALUES ('$qLength', '$customPostID')")
		){
			add_settings_error('wimpq-photoq', 'upload-db-insert-error',
				sprintf(__('The file upload failed with the following error: Could not add DB entry to table %s', 'PhotoQ'), $this->getFullName()), 
				'error'
			);
			return false;
		}
		return true;
	}
	
	public function setQueuePosition($id, $position){
		PhotoQHelper::debug('update position: ' . $id . ' / ' . $position);
		$this->_wpdb->query("UPDATE  ".$this->getFullName()." SET q_position = '$position' WHERE q_img_id = '$id'");	
	}
	
	public function setPostID($id, $postID){
		$this->_wpdb->query("UPDATE  ".$this->getFullName()." SET q_fk_post_id = '$postID' WHERE q_img_id = '$id'");	
	}
	
	public function setEditedFlag($id){
		$this->_wpdb->query("UPDATE ".$this->getFullName()." SET q_edited = 1 WHERE q_img_id = $id");
	}
	
	public function getAllQueuedPhotoCustomPostIDs(){
		return $this->_wpdb->get_col("SELECT q_fk_post_id FROM ".$this->getFullName()." WHERE 1");
	}
	
	public function getQueueByPosition()
	{
		return $this->_wpdb->get_results(
			'SELECT * FROM '.$this->getFullName().', ' .$this->_wpdb->posts.
			" WHERE q_fk_post_id = ID 
			AND post_type = '". PhotoQQueuedPostType::POST_TYPE_NAME ."'
			ORDER BY q_position
		");
	}
	
	public function deleteQueueEntry($id, $postID, $position){
		//delete DB entry
		$this->_wpdb->query("DELETE FROM ".$this->getFullName()." WHERE q_img_id = $id");
		//update queue positions
		$this->_wpdb->query("UPDATE  ".$this->getFullName()." SET q_position = q_position-1 WHERE q_position > '$position'");

		//delete custom post type
		wp_delete_post($postID, true);
	}
	
	/**
	 * Sorts the queue according to the given criterion.
	 * @param $criterion
	 * @return unknown_type
	 */
	public function sortQueue($criterion){
		//get the sorted ids of the images in the queue.
		$sortedIds = $this->_wpdb->get_col('SELECT q_img_id
			FROM '.$this->getFullName().', ' .$this->_wpdb->posts. "
			WHERE q_fk_post_id = ID AND post_type = '". PhotoQQueuedPostType::POST_TYPE_NAME ."' 
			ORDER BY " . $this->_getSortOrderByClause($criterion));
		
		//randomize?
		if($criterion === 'random') shuffle($sortedIds);
		
		//sort the database accordingly
		$this->_multiRowSort($sortedIds);
	}
	
	/**
	 * Returns the ORDER BY clause that is used when ordering the queue positions.
	 * @param $criterion
	 * @return unknown_type
	 */
	private function _getSortOrderByClause($criterion = 'id'){
		$order = "q_img_id";
		switch($criterion){
			case "date_desc":
				$order = "post_date DESC, post_excerpt, q_img_id";
				break;
			case "date_asc":
				$order = "post_date ASC, post_excerpt, q_img_id";
				break;
			case "title_asc":
				$order = "post_title ASC, post_date, post_excerpt, q_img_id";
				break;
			case "title_desc":
				$order = "post_title DESC, post_date, post_excerpt, q_img_id";
				break;
			case "filename_asc":
				$order = "post_excerpt ASC, post_date, q_img_id";
				break;
			case "filename_desc":
				$order = "post_excerpt DESC, post_date, q_img_id";
				break;
		}
		return $order;
	}
	
	/**
	 * Updates DB such that positions correspond to the ordering of the IDs of the array given.
	 * Done in a server friendly way, in only one single query.
	 * @param $sortedIds
	 * @return unknown_type
	 */
	private function _multiRowSort($sortedIds){
		$multiRowSortQuery = "UPDATE ".$this->getFullName()." SET q_position = CASE q_img_id ";
		foreach($sortedIds as $pos => $id){
			$multiRowSortQuery .= "WHEN '$id' THEN '$pos' ";
		}
		$multiRowSortQuery .= "ELSE q_position END";
		$this->_wpdb->query($multiRowSortQuery);
	}
	
	
}