<?php
class PhotoQ_Command_SortQueue extends PhotoQ_Command_QueueCommand
{
	public function execute(){
		$this->_queue->sort($_POST['sort_criterion']);
	}
}