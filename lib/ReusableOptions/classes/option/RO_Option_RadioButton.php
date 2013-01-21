<?php
/**
 * @package ReusableOptions
 */
 

/**
 * The RO_Option_RadioButton:: class represents a single radio button.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_RadioButton extends RO_Option_SelectableComposite
{
	
	/**
	 * PHP5 type constructor
	 */
	function __construct($defaultValue, $label = '', 
					$textBefore = '', $textAfter = '')
	{
		parent::__construct('', $defaultValue, $label, $textBefore, $textAfter);
	}
	
	
	
	
}


