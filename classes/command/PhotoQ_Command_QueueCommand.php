<?php
abstract class PhotoQ_Command_QueueCommand implements PhotoQ_Command_Executable
{
	protected $_queue;
	
	public function __construct(PhotoQQueue $queue){
		$this->_queue = $queue;
	}
	
}