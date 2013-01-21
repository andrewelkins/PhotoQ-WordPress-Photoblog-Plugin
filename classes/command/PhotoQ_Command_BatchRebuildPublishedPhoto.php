<?php
class PhotoQ_Command_BatchRebuildPublishedPhoto extends PhotoQ_Command_BatchAtomic 
												implements PhotoQ_Command_Batchable
{
	
	private $_photoId;
	
	private $_changedSizes;
	private $_updateExif;
	private $_changedViews;
	private $_updateOriginalFolder;
	private $_oldFolder;
	private $_newFolder;
	private $_addedTags;
	private $_deletedTags;
	
	public function __construct($photoId, 
		$changedSizes, $updateExif, $changedViews, $updateOriginalFolder, 
		PhotoQ_File_SourceDestinationPair $srcDest, $addedTags, $deletedTags
	){
		$this->_photoId = $photoId;
		$this->_changedSizes = $changedSizes;
		$this->_updateExif = $updateExif;
		$this->_changedViews = $changedViews;
		$this->_updateOriginalFolder = $updateOriginalFolder;
		$this->_oldFolder = $srcDest->getSource();
		$this->_newFolder = $srcDest->getDestination();
		$this->_addedTags = $addedTags;
		$this->_deletedTags = $deletedTags;
	}
	
	public function serialize() {
		return serialize($this->_buildSerializableArray()); 
  	}
  	
  	/**
  	 * Returns all fields of this class in an array that can be serialized.
  	 * @return array
  	 */
  	private function _buildSerializableArray(){
  		$result = array();
  		foreach($this->_getSerializableFields() as $field)
  			$result[$field] = $this->{$field};
  		$result['baseSerialized'] = parent::serialize();
  		return $result;
  	}
  	
	/**
  	 * Returns all fields of the class of the current instance 
  	 * without the fields of the parent classes.
  	 * @return array
  	 */
  	private function _getSerializableFields(){
  		$reflection = new ReflectionClass($this);
        $parentReflection = $reflection->getParentClass();
    	return array_diff(
    		array_keys($reflection->getdefaultProperties()), 
    		array_keys($parentReflection->getdefaultProperties()));
  	}
  	
  	public function unserialize($serialized) {
  		$unserialized = unserialize($serialized);
  		foreach($this->_getSerializableFields() as $field)
  			$this->{$field} = isset($unserialized[$field]) ? $unserialized[$field] : null;
  		parent::unserialize($unserialized['baseSerialized']);
  	}
	
	protected function _executeAtom(){
		//get photo
		$db = PhotoQ_DB_DB::getInstance();
		$photo = $db->getPublishedPhoto($this->_photoId);
		//rebuild it		
		if($photo)
			$photo->rebuild($this->_changedSizes, $this->_updateExif, 
				$this->_changedViews, $this->_updateOriginalFolder, 
				$this->_oldFolder, $this->_newFolder, $this->_addedTags, 
				$this->_deletedTags);
	}
	
}