<?php
class PhotoQ_Command_ClearQueue extends PhotoQ_Command_QueueCommand
{
	public function execute(){
		$this->_queue->deleteAll();
	}
}