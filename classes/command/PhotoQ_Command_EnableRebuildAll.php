<?php
class PhotoQ_Command_EnableRebuildAll extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->enableRebuildAll();
	}
}