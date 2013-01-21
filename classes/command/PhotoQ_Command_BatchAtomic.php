<?php
/**
 * An atomic command can be queued in the batch processor for 
 * asynchronous processing. An atomic command is not split up 
 * over several Ajax invocations, it is either 0% completed if
 * it was never executed, or 100% completed if it was executed
 * once.
 * 
 * Subclass this class if you have small jobs that you want to 
 * run on the batch processor without having to manage the 
 * completion status yourself. Typically, you will want to group
 * several atomic commands in a batch macro command.
 *
 */
abstract class PhotoQ_Command_BatchAtomic implements PhotoQ_Command_Batchable
{
	private $_percentageDone = 0.0;
	
	final public function execute(){
		$this->_executeAtom();
		$this->_percentageDone = 1.0;	
	}
	
	public function serialize() { 
    	return serialize(array(
    		'_percentageDone' => $this->_percentageDone
    	)); 
  	}
  	
  	public function unserialize($serialized) {
  		$unserialized = unserialize($serialized);
		$this->_percentageDone = 
			isset($unserialized['_percentageDone']) ? $unserialized['_percentageDone'] : null;
  	}
	
	/**
	 * This function contains the application logic that should
	 * be executed when the command runs.
	 */
	abstract protected function _executeAtom();
	
	final public function getPercentageDone(){
		return $this->_percentageDone;
	}
	
	final public function isDone(){
		return $this->_percentageDone == 1.0;
	}
	
}


