<?php
class PhotoQ_Command_PublishMulti extends PhotoQ_Command_QueueCommand
{
	public function execute(){
		$this->_queue->publishMulti();
	}
}