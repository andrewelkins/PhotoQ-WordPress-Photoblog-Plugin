<?php
class PhotoQ_Command_PrepareOptionsPanel extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->prepareOptionsPanel();
	}
}