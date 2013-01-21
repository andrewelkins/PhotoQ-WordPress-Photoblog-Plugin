<?php
class PhotoQ_Command_ShowPhotoUploadPanel extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->showPhotoUploadPanel();
	}
}