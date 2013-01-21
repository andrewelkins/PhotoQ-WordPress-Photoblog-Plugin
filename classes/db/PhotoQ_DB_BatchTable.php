<?php
/**
 * Provides CRUD functionality for PhotoQ Batch DB table.
 * @author flury
 *
 */
class PhotoQ_DB_BatchTable extends PhotoQ_DB_Table
{
	
	const NAME = 'photoqbatch';
	
	public function __construct(){
		parent::__construct(self::NAME);
	}
	
	protected function _getFieldDefinition(){
		return '(
			bid int(10) NOT NULL AUTO_INCREMENT,
			timestamp int(11) NOT NULL,
			batch longtext,
			PRIMARY KEY  (bid)
		)';
	}
	
	/**
	 * Insert a new batch into the database and return its id.
	 * @return unknown_type
	 */
	public function insertBatch(){
		if(!$this->_wpdb->query('INSERT INTO '.$this->getFullName().' (timestamp) VALUES ('.time().')'))
			return false;
		return $this->_wpdb->insert_id;
	}
	
	/**
	 * Update batch with given id in the database
	 * @param $id
	 * @param $batchSets
	 * @return unknown_type
	 */
	public function updateBatch($id, $batchSets){
		$this->_wpdb->query('UPDATE ' .$this->getFullName(). " SET batch='".mysql_real_escape_string(serialize($batchSets))."' WHERE bid = '$id'");
	}
	
	/**
	 * Remove batch with specified id from the database
	 * @param $id int	id to remove
	 * @return unknown_type
	 */
	public function deleteBatch($id){
		//also remove those that are older than 1 day
		$this->_wpdb->query('DELETE FROM ' .$this->getFullName(). " WHERE bid = '$id' OR timestamp < " . (time() - 86400) );
	}
	
	/**
	 * Returns the batch sets associated with batch of given id.
	 * @param $id
	 * @return unknown_type
	 */
	public function getQueuedBatchCommands($id){
		$setObj = ($this->_wpdb->get_row('SELECT batch FROM ' .$this->getFullName(). " WHERE bid='$id'"));
		PhotoQHelper::debug('db getBatchSets: ' . print_r(($setObj->batch),true));
		PhotoQHelper::debug('db getBatchSets unser: ' . print_r(unserialize($setObj->batch),true));
		
		return unserialize($setObj->batch);
	}
}