<?php
/**
 * The RO_Validation_GDAvailable:: checks whether gd image library is available.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Validation_GDAvailable extends RO_Validation_InputTest
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
		if (!function_exists("gd_info")) {
    		$errMsg =  "Warning: PHP GD library does not seem to be activated/installed on your server. GD is however required for this plugin to work properly.";
			$this->raiseErrorMessage($errMsg);
			return false;
		}
		return true;
	}
	
	
}