<?php
/**
 * Commands that are to be executed asynchronously by the batch processor
 * need to implement this interface.
 */
interface PhotoQ_Command_Batchable extends PhotoQ_Command_Executable, Serializable
{	
	/**
	 * Returns percentage between 0.0 and 1.0 indicating to what extent
	 * the command is completed.
	 * @return float
	 */
	public function getPercentageDone();
	
	/**
	 * Indicates whether the command has fully completed or not.
	 * @return boolean
	 */
	public function isDone();
}