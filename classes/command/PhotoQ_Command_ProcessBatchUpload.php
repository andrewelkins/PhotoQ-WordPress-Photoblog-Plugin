<?php
class PhotoQ_Command_ProcessBatchUpload extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->processBatchUpload();
	}
}