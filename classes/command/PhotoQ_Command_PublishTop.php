<?php
class PhotoQ_Command_PublishTop extends PhotoQ_Command_QueueCommand
{
	public function execute(){
		$this->_queue->publishTop();
	}
}