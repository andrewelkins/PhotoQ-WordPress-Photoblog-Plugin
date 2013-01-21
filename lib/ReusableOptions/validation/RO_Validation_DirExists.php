<?php
/**
 * @package ReusableOptions
 */
 

/**
 * The RO_Validation_DirExists:: checks whether input is existing directory (relative to WP base directory).
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Validation_DirExists extends RO_Validation_InputTest
{
	
	/**
	 * The dir to be tested. If empty, target->getValue() is tested.
	 *
	 * @var unknown_type
	 */
	var $_dir;
	
	function __construct($errMsgCallback, $dir = '', $errMsgPrefix = '', $customErrMsg = '')
	{
		parent::__construct($errMsgCallback, $errMsgPrefix, $customErrMsg);
		$this->_dir = $dir;
	}
	
	/**
	 * Concrete implementation of the validate() method. This methods determines 
	 * whether input validation passes or not.
	 * @param object RO_Option_ReusableOption $target 	The option to validate.
	 * @return String 	The error message created by this test.
	 * @access public
	 */
	function validate($target)
	{	
		$dirname = $this->_dir ? $this->_dir : $target->getValue();
		//$dirname = ABSPATH . $dirname;
		$dirname = path_join( ABSPATH, trim($dirname) );
		//convert backslashes (windows) to slashes
		$dirname = str_replace('\\', '/', $dirname);

		if (!is_dir($dirname)) {
    		$errMsg =  "The directory ". $dirname . " does not exist on your server.";
    		$this->raiseErrorMessage($errMsg);
			return false;
		}
		return true;
	}
	
	
}


