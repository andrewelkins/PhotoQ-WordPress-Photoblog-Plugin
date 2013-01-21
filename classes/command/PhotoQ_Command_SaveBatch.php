<?php
class PhotoQ_Command_SaveBatch extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->saveBatch();
	}
}