<?php
class PhotoQ_Command_PrepareManagePanel extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->prepareManagePanel();
	}
}