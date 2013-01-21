<?php
class PhotoQ_Command_UploadWatermark extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->uploadWatermark();
	}
}