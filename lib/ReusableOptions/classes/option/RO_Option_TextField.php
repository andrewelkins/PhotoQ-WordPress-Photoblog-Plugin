<?php
/**
 * @package ReusableOptions
 */

class RO_Option_TextField extends RO_Option_ReusableOption
{

	/**
	 * Size of the textfield.
	 *
	 * @var integer
	 * @access private
	 */
	var $_size;
	
	/**
	 * Maximum length of textfield content
	 *
	 * @var integer
	 * @access private
	 */
	var $_maxlength;
	
	/**
	 * Any tests that the input validation of this TextField should pass.
	 *
	 * @var array object InputTest
	 * @access private
	 */
	var $_tests;
	
	//var $_updateTests;
	
	/**
	 * PHP5 type constructor
	 */
	function __construct($name, $defaultValue = '', $label = '', 
				$textBefore = '', $textAfter = '', $size = 50, $maxlength = 100)
	{
		parent::__construct($name, $defaultValue, $label, $textBefore, $textAfter);
		$this->_size = $size;
		$this->_maxlength = $maxlength;
		$this->_tests = array();
		//$this->_updateTests = array();
	}
	
	
	/**
	 * Getter for size field.
	 * @return integer		The size of the textField.
	 * @access public
	 */
	function getSize()
	{
		return $this->_size;
	}
	
	/**
	 * Setter for size field.
	 * @param integer $size		The new size of the textField.
	 * @access public
	 */
	function setSize($size)
	{
		$this->_size = $size;
	}
	
	/**
	 * Getter for maxlength field.
	 * @return integer		The maximum length of the textField.
	 * @access public
	 */
	function getMaxLength()
	{
		return $this->_maxlength;
	}
	
	/**
	 * Setter for maxlength field.
	 * @param integer $length	The new maximum length of the textField.
	 * @access public
	 */
	function setMaxLength($length)
	{
		$this->_maxlength = $length;
	}
	
	
	/**
	 * Add an input valdiation test to the textfield.	
	 * 
	 * @param object InputValidationTest $test  The test to be added.
	 * @return boolean	True if test could be added, false otherwise.
	 * @access public
	 */
	function addTest($test, $mustValidate2Change = false)
	{	
		$this->_tests[] = $test;
		if($mustValidate2Change)
			$this->_updateTests[] = $test;
		return true;
	}
	
	/**
	 * Overrides abstract method. Does input validation of textfield options.
	 * 
	 * @return array string			The status messages created by the validation procedure.
	 * @access public
	 */
	function validate()
	{
		foreach ( array_keys($this->_tests) as $index ) {
			if(!$this->_tests[$index]->validate($this)){
				return false;
			}
		}	
		return true;
	}
	
}