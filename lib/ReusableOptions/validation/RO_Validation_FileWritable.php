<?php
/**
 * @package ReusableOptions
 */
 

/**
 * The RO_Validation_FileWritable:: checks whether input file/dir is writable (for php user).
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Validation_FileWritable extends RO_Validation_InputTest
{
	
	/**
	 * The file to be tested. If empty, target->getValue() is tested.
	 *
	 * @var string
	 */
	var $_file;
	
	function __construct($errMsgCallback, $file = '', $errMsgPrefix = '', $customErrMsg = '')
	{
		parent::__construct($errMsgCallback, $errMsgPrefix, $customErrMsg);
		$this->_file = $file;
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
		$filename = $this->_file ? $this->_file : $target->getValue();
		$filename = path_join( ABSPATH, $filename );
		//$filename = ABSPATH . $filename;
		if (!is_writable($filename)) {
    		$errMsg =  "The file ". $filename . " is not writable, check permissions.";
			$this->raiseErrorMessage($errMsg);
			return false;
		}
		return true;
	}
	
	
}


