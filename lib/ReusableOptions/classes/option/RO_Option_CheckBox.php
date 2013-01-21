<?php
/**
 * @package ReusableOptions
 */
 

class RO_Option_CheckBox extends RO_Option_Composite
{
	
	/**
	 * Check whether we find a value for this option in the array pulled from 
	 * the database. If so adopt this value. Pass the array on to all the children
	 * such that they can do the same.
	 * 
	 * @param array $storedOptions		Array pulled from database.
	 * @access public
	 */
	function load($storedOptions)
	{
		if(is_array($storedOptions)){
			if(array_key_exists($this->_name, $storedOptions)){
				$this->setValue($storedOptions[$this->_name]);
			}
			parent::load($storedOptions);
		}elseif($storedOptions) //option was not stored in an associative array
			$this->setValue($storedOptions);
	}
	
	
	/**
	 * Stores own values in addition to children values in associative array that
	 * can be stored in Wordpress database.
	 * 
	 * @return array $result		Array of options to store in database.
	 * @access public
	 */
	function store()
	{
		$result = array();
		$result[$this->_name] = $this->_value;
		$result = array_merge(parent::store(), $result);
		return $result;
	}
	
	/**
	 * Setter for value field.
	 * @param string		The new value of the option.
	 * @access public
	 */
	function setValue($value)
	{
		switch($value){
			case '0':
			case '1':
				$this->_value = $value;
				break;
			default:
				die('Error in RO_Option_CheckBox::setValue(): "Checkbox value must be 0 or 1"');
				break;
		}
	}


}


