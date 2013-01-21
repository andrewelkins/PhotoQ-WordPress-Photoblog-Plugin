<?php
/**
 * @package ReusableOptions
 */
 
class RO_Option_HiddenInputField extends RO_Option_ReusableOption
{
	
	/**
	 * PHP5 type constructor
	 */
	function __construct($name, $defaultValue)
	{
		parent::__construct($name, $defaultValue, '','','');
	}
	
	
	
}


