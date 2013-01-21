<?php
class PhotoQ_Command_RebuildPost extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->rebuildPost();
	}
}