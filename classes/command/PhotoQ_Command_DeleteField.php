<?php
class PhotoQ_Command_DeleteField extends PhotoQ_Command_DatabaseCommand
{
	public function execute(){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fieldTable->removeField(esc_attr($_GET['id']));
	}
}