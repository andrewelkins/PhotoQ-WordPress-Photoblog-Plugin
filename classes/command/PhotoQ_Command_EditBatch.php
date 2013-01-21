<?php
class PhotoQ_Command_EditBatch extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->editBatch();
	}
}