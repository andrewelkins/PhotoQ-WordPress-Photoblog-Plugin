<?php
class PhotoQ_Command_DeletePhoto extends PhotoQ_Command_QueueCommand
{
	public function execute(){
		$this->_queue->deletePhotoById(esc_attr($_GET['id']));
	}
}