<?php
class PhotoQ_Command_FixPermissions extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->fixPermissions();
	}
}