<?php
class PhotoQ_Command_RenameField extends PhotoQ_Command_DatabaseCommand
{
	public function execute(){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fieldTable->renameField(esc_attr($_POST['field_id']), esc_attr($_POST['field_name']));
	}
}