<?php
class PhotoQ_Command_ShowFTPPhotoUploadPanel extends PhotoQ_Command_ShowPhotoUploadPanel
{
	public function execute(){
		if(!is_multisite())
			parent::execute();
	}
}