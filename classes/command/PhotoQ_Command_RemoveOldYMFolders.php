<?php
class PhotoQ_Command_RemoveOldYMFolders extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->removeOldYMFolders();
	}
}