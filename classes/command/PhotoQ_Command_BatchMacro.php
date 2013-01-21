<?php
/**
 * Implements the Composite patterns to provide a list of several
 * commands to be executed in asynchronous Ajax batchs.
 */
class PhotoQ_Command_BatchMacro extends PhotoQ_Command_Macro 
								implements PhotoQ_Command_Batchable
{
	private $_numberOfCompletedCommands = 0;
	
	public function serialize() {
    	return serialize(array(
    		'_numberOfCompletedCommands'	=> $this->_numberOfCompletedCommands,
    		'baseSerialized'				=> parent::serialize()		
    	)); 
  	}
  	
  	public function unserialize($serialized) {
  		$unserialized = unserialize($serialized);
		$this->_numberOfCompletedCommands = 
			isset($unserialized['_numberOfCompletedCommands']) ? $unserialized['_numberOfCompletedCommands'] : null;
		parent::unserialize($unserialized['baseSerialized']);
  	}
	
	public function getPercentageDone(){
		if(!$this->hasCommands())
			return 1.0;
		else
			return 1.0 * $this->_getPercentageOfCommandsCompleted()
				+ $this->_getAverageCompletionPercentage() * 
				$this->_getPercentageOfCommandsRemaining();	
	}
	
	private function _getPercentageOfCommandsCompleted(){
		return $this->_numberOfCompletedCommands/$this->_getTotalCommands();
	}
	
	private function _getTotalCommands(){
		return $this->_numberOfCompletedCommands + $this->getNumberOfCommands();
	}
	
	private function _getPercentageOfCommandsRemaining(){
		return $this->getNumberOfCommands()/$this->_getTotalCommands();
	}
	
	private function _getAverageCompletionPercentage(){
		$done = 0.0;
		foreach($this as $command){
			$done += $command->getPercentageDone();
		}
		return $done/$this->getNumberOfCommands();
	}
	
	public function isDone(){
		return $this->getPercentageDone() == 1.0;
	}
	
	/**
	 * With a batch macro, only the first command is 
	 * executed. The first command executes until it
	 * is completed, at which point it is removed from
	 * the list.
	 * (non-PHPdoc)
	 * @see wp-content/plugins/photoq-photoblog-plugin/classes/command/PhotoQ_Command_Macro#execute()
	 */
	public function execute(){
		if($this->hasCommands())
			$this->_executeFirstCommand();
	}
	
	/**
	 * Executes the first command and removes it from the
	 * list of commands if it is completed.
	 * @return unknown_type
	 */
	private function _executeFirstCommand(){
		$firstCommand = $this->shiftCommand();
		$firstCommand->execute();
		if($firstCommand->isDone())
			$this->_numberOfCompletedCommands++;
		else
			$this->unshiftCommand($firstCommand);
	}

	/**
	 * Overrides addCommand() function to make sure only batchable commands are added.
	 * (non-PHPdoc)
	 * @see wp-content/plugins/photoq-photoblog-plugin/classes/command/PhotoQ_Command_Macro#addCommand($command)
	 */
	public function addCommand(PhotoQ_Command_Batchable $command){
		parent::addCommand($command);
	}
	
}