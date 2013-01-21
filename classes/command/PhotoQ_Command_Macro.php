<?php
/**
 * Implements the Composite patterns to provide a list of several
 * commands to be executed. Further, it allows iteration over the
 * stored commands.
 */
class PhotoQ_Command_Macro 
implements PhotoQ_Command_Executable, IteratorAggregate, Serializable
{
	/**
	 * @var PhotoQ_Command_Executable
	 */
	private $_commands = array();
	
	public function __construct(array $commands = array()){
		foreach($commands as $command)
			$this->addCommand($command);
	}
	
	public function serialize() { 
    	return serialize(array(
    		'_commands' => $this->_commands
    	)); 
  	}
  	
  	public function unserialize($serialized) {
  		$unserialized = unserialize($serialized);
		$this->_commands = 
			isset($unserialized['_commands']) ? $unserialized['_commands'] : null;
  	}	 
  	
	public function execute(){
		foreach($this as $command){
			$command->execute();
		}
	}
	
	public function getIterator() {
        return new ArrayIterator($this->_commands);
    }
	
	public function addCommand(PhotoQ_Command_Executable $command){
		$this->_commands[] = $command;
	}
	
	public function getNumberOfCommands(){
		return count($this->_commands);
	}
	
	public function hasCommands(){
		return $this->getNumberOfCommands() >= 1;
	}
	
	public function shiftCommand(){
		return array_shift($this->_commands);
	}
	
	public function unshiftCommand(PhotoQ_Command_Executable $firstCommand){
		array_unshift($this->_commands, $firstCommand);
	}
	
}