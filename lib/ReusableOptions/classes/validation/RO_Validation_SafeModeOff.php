<?php
/**
 * A suite of classes that allows to test the PHP configuration of the server.
 * @package ReusableOptions
 */
 

/**
 * The RO_Validation_SafeModeOff:: checks whether php safe mode is off.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Validation_SafeModeOff extends RO_Validation_InputTest
{
	
	/**
	 * Concrete implementation of the validate() method. This methods determines 
	 * whether input validation passes or not.
	 * @param object RO_Option_ReusableOption $target 	The option to validate.
	 * @return String 	The error message created by this test.
	 * @access public
	 */
	function validate($target)
	{	
		if (ini_get('safe_mode')) {
    		$errMsg =  "Warning: You are running PHP with safe_mode on. This plugin requires safe_mode off for correct functioning.";
			$this->raiseErrorMessage($errMsg);
			return false;
		}
		return true;
	}
	
	
}