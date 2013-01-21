<?php
class PhotoQ_Command_AddField extends PhotoQ_Command_DatabaseCommand
{
	public function execute(){
		$fieldname = esc_attr($_POST['newFieldName']);
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		if($fieldTable->insertField($fieldname))
			$this->_db->addInitialFieldMeta($fieldname);
	}
}