<?php
/**
 * Provides CRUD functionality for PhotoQ Field DB table.
 * @author flury
 *
 */
class PhotoQ_DB_CustomFieldTable extends PhotoQ_DB_Table
{
	
	const NAME = 'photoqfields';
	
	public function __construct(){
		parent::__construct(self::NAME);
	}
	
	protected function _getFieldDefinition(){
		return "(
			q_field_id bigint(20) NOT NULL AUTO_INCREMENT,
			q_field_name varchar(200) NOT NULL default '',
			PRIMARY KEY  (q_field_id)
		)";
	}
	
	/**
	 * Checks whether the field with provided name is already defined.
	 * @param string $name
	 * @return boolean
	 */
	public function exists($name){
		return in_array($name, $this->getFieldNames());
	}
	
	/**
	 * Inserts a new custom field into the database.
	 * 
	 * @param string $name		The name of the field to be created.
	 * @access public
	 */
	public function insertField($name)
	{
		//only add if field doesn't exist yet
		if($this->exists($name))
			add_settings_error(
				'wimpq-photoq', 'field-exists',
				sprintf(__('Please choose another name, a meta field with name "%s" already exists.', 'PhotoQ'), $name), 
				'error'
			);		
		else{
			// instantiate an OptionController
			$oc = PhotoQ_Option_OptionController::getInstance();

			//do not add if a view with same name exists
			$viewNames = $oc->getViewNames();
			if(in_array($name, $viewNames)){
				add_settings_error(
					'wimpq-photoq', 'view-exists',
					sprintf(__('Please choose another name, a view with name "%s" already exists.', 'PhotoQ'), $name), 
					'error'
				);	
			}else{ // now we can add the field
					
				//remove whitespace as this will also be used as mysql column header
				$name = preg_replace('/\s+/', '_', $name);
				$this->_wpdb->query("INSERT INTO ".$this->getFullName()." (q_field_name) VALUES ('$name')");
				add_settings_error(
					'wimpq-photoq', 'field-created',
					sprintf(__('The field with name "%s" was successfully added.', 'PhotoQ'), $name), 
					'updated'
				);	
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Remove a custom field from the database.
	 * 
	 * @param int $id		The id of the field to be removed.
	 * @access public
	 */	
	public function removeField($id)
	{	
		// instantiate an OptionController
		$oc = PhotoQ_Option_OptionController::getInstance();
		
		//get the name before deleting
		$name = $this->_wpdb->get_var("SELECT q_field_name FROM ".$this->getFullName()." WHERE q_field_id = $id");
	
		//delete DB entry
		$this->_wpdb->query("DELETE FROM ".$this->getFullName()." WHERE q_field_id = '$id'");
	
		//delete from post meta table
		delete_post_meta_by_key($name);
		
		add_settings_error(
			'wimpq-photoq', 'field-deleted',
			sprintf(__('The field with name "%s" was successfully deleted.', 'PhotoQ'), $name), 
			'updated'
		);
	} 
	
	/**
	 * Rename an exising custom field.
	 * 
	 * @param int $id				The id of the field to be renamed.
	 * @param string $newName		The new name of the field to be renamed.
	 * @access public
	 */
	public function renameField($id, $newName)
	{
		// TODO: prohibit two fields with same name

		// instantiate an OptionController
		$oc = PhotoQ_Option_OptionController::getInstance();
		
		//get the old name
		$oldName = $this->_wpdb->get_var("SELECT q_field_name FROM ".$this->getFullName()." WHERE q_field_id = $id");
	
		//remove whitespace as this will also be used as mysql column header
		$newName = preg_replace('/\s+/', '_', $newName);
	
		//update DB entry
		$this->_wpdb->query("UPDATE ".$this->getFullName()." SET q_field_name = '$newName' WHERE q_field_id = '$id'");
	
		//update already posted posts
		$this->_wpdb->query("UPDATE $this->POSTMETA_TABLE SET meta_key = '$newName' WHERE meta_key = '$oldName'");
	
	}
	
	public function getAllFields()
	{
		return $this->_wpdb->get_results("
			SELECT * FROM ".$this->getFullName()."
			WHERE 1 ORDER BY q_field_name
		");
	}
	
	public function getFieldNames()
	{
		$fields = $this->getAllFields();
		$result = array();
		foreach ($fields as $field) {
			$result[] = $field->q_field_name;
		}
		return $result;
	}
	
}