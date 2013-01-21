<?php
class PhotoQ_Command_PreparePanel extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->preparePanel();
	}
}