<?php
class PhotoQ_DB_DB implements PhotoQSingleton
{
	const VERSION_FIELD = 'wimpq_version';
	
	private static $_singletonInstance;
	
	/**
	 * The wordpress database object to interface with wordpress database
	 * @var Object
	 * @access private
	 */
	var $_wpdb;

	/**
	 * Will hold all the database table objects of database tables 
	 * that are defined by photoq.
	 * @var unknown_type
	 */
	private $_photoQTables;
	
	private function __construct()
	{
		global $wpdb;
		
		// set wordpress database
		$this->_wpdb = $wpdb;
		
		// register custom photoq database tables
		$this->_photoQTables = array(
			new PhotoQ_DB_QueueTable(),
			new PhotoQ_DB_BatchTable(),
			new PhotoQ_DB_CustomFieldTable()
		);
		
	}
	
	public static function getInstance()
	{
		if (!isset(self::$_singletonInstance)) {
			self::$_singletonInstance = new self();
		}
		return self::$_singletonInstance;
	}
	
	function getLastPostDate(){
		return $this->_wpdb->get_var("SELECT post_date FROM {$this->_wpdb->posts} WHERE post_status = 'publish' ORDER BY post_date DESC");
	}

	/**
	 * As so many other people, we hate the new revision feature of wordpress ;-)
	 * We don't store any revisions of photoQ posts. This function removes all
	 * revisions of post with id $postID.
	 *
	 * @param unknown_type $postID
	 * @return unknown
	 */
	function removeRevisions($postID)
	{
		return $this->_wpdb->get_results('
			DELETE FROM '.$this->_wpdb->posts."
			WHERE post_type = 'revision' AND post_parent = $postID
		");
		
	}
	
	function getAllPublishedPhotos()
	{
		$photos = array();
		$results = $this->_wpdb->get_results("
			SELECT ID, post_title, meta_value FROM ".$this->_wpdb->posts.", ".$this->_wpdb->postmeta."  
			WHERE ".$this->_wpdb->posts.".ID = ".$this->_wpdb->postmeta.".post_id AND ".$this->_wpdb->postmeta.".meta_key = 'photoQPath'");
		foreach ($results as $result)
			$photos[] = new PhotoQ_Photo_PublishedPhoto($result->ID, $result->post_title, $result->meta_value);
		
		return $photos;
	}
	
	function getAllPublishedPhotoIDs()
	{
		return $this->_wpdb->get_col("
			SELECT ID FROM ".$this->_wpdb->posts.", ".$this->_wpdb->postmeta."  
			WHERE ".$this->_wpdb->posts.".ID = ".$this->_wpdb->postmeta.".post_id AND ".$this->_wpdb->postmeta.".meta_key = 'photoQPath'");
		
	}
	
	/**
	 * 
	 * @param $postID
	 * @return object PhotoQ_Photo_PublishedPhoto
	 */
	function getPublishedPhoto($postID)
	{
		$result = $this->_wpdb->get_row('
			SELECT post_title, meta_value FROM '.$this->_wpdb->posts.', '.$this->_wpdb->postmeta.' 
			WHERE '.$this->_wpdb->posts.".ID = '$postID' AND ".$this->_wpdb->posts.".ID = ".$this->_wpdb->postmeta.".post_id AND ".$this->_wpdb->postmeta.".meta_key = 'photoQPath'");
		if(is_null($result)){
			add_settings_error('wimpq-photoq', 'post-not-found',
					sprintf(__('The post with ID "%s" does not seem to exist.', 'PhotoQ'), $postID), 'error');
			return NULL;
		}
		
		return new PhotoQ_Photo_PublishedPhoto($postID, $result->post_title, $result->meta_value);
		
	}
	
	public function updateQueue($id, $postID, $title, $descr, $tags, $slug, $authorID, $pnum = 0)
	{		
		$qTable = new PhotoQ_DB_QueueTable();
		$qTable->setEditedFlag($id);
		
		wp_update_post(array(
			'ID' => $postID,
			'post_title' => $title,
			'post_content' => $descr,
			'tags_input' => $tags,
			'post_name' => $slug,
			'post_author' => $authorID
		));
	
		$taxonomies = new PhotoQ_Util_Taxonomies();
		$taxonomies->updatePostTaxonomies($postID);
		
		$this->_updatePostFieldMeta($postID, $pnum);
	}
	
	private function _updatePostFieldMeta($postID, $pnum){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$results = $fieldTable->getAllFields();
		if($results){
			foreach ($results as $field_entry) {
				$newValue = $_POST["$field_entry->q_field_name"][$pnum];
				update_post_meta($postID, $field_entry->q_field_name, $newValue);
			}
		}
	}
	
	public function addInitialFieldMeta($name){
		$qTable = new PhotoQ_DB_QueueTable();
		$ids = $qTable->getAllQueuedPhotoCustomPostIDs();
		foreach ($ids as $id) {
			add_post_meta($id, $name, '');
		}
		$this->addFieldToPublishedPosts($name);
	}
	
	/**
	 * Adds a custom field with name "$name" to all published photoq posts.
	 * Only adds to a post if a field with the same name does not yet exist for this post.
	 * @param $name	String	the name of the field to be added
	 * @return unknown_type
	 */
	public function addFieldToPublishedPosts($name){
		//select all photoq posts that do not have the new field yet
		$results = $this->_wpdb->get_results(
			"SELECT ID FROM" . $this->_wpdb->posts ."," . $this->_wpdb->postmeta .  "WHERE 
				ID = post_id && meta_key = 'photoQPath' && ID NOT IN 
					(SELECT post_id FROM ". $this->_wpdb->postmeta. "WHERE `meta_key` = '$name')
			");
		//add the field to each of these posts
		if($results){
			foreach ($results as $postEntry) {
				add_post_meta($postEntry->ID, $name, '', true);
			}
		}
	}
	
	
	
	public function addCategoryToAllPhotoQPosts($catID){
		$postIDs = $this->getAllPublishedPhotoIDs();
		$qTable = new PhotoQ_DB_QueueTable();
		$postIDs = array_merge($postIDs, $qTable->getAllQueuedPhotoCustomPostIDs());
		foreach($postIDs as $id)
			$this->_insertCategory($id, $catID);
	}
	
	private function _insertCategory($postID, $catId){
		$cats = wp_get_post_categories($id);
		$cats[] = $catID;
		wp_set_post_categories($postID, $cats);
	}
	
	/**
	 * Check whether a certain column exists in a certain table
	 *
	 * @param string $tableName
	 * @param string $colName
	 * @return boolean
	 */
	/*private function _colExists($tableName, $colName){
		// Fetch the table column structure from the database
		$colStructures = $this->_wpdb->get_results("DESCRIBE $tableName;");	
		// Check for existence of column $colName
		$colFound = false;
		foreach($colStructures as $colStruct){
			if((strtolower($colStruct->Field) == $colName)){
				$colFound = true;
				break;
			}
		}
		return $colFound;
	}*/
	
	/**
	 * Check whether a certain database table exists.
	 *
	 * @param string $tableName
	 * @return boolean
	 */
	/*private function _tableExists($tableName){
		$tables = $this->_wpdb->get_col("SHOW TABLES;");
		$tableFound = false;
		foreach($tables as $table){
			if(strtolower($table) == $tableName){
				$tableFound = true;
				break;
			}
		}
		return $tableFound;
	}*/
	
	/**
	 * Upgrades/Installs database tables.
	 *
	 * @access public
	 */
	public function upgrade()
	{
		foreach($this->_photoQTables as $table)
			$table->upgradeDB();
		
		if(get_option(self::VERSION_FIELD))
			update_option(self::VERSION_FIELD, PhotoQ::VERSION);
		else
			add_option(self::VERSION_FIELD, PhotoQ::VERSION);
		
	}
	
	public static function didVersionChange(){
		return PhotoQ::VERSION != get_option(self::VERSION_FIELD);
	}
	
}