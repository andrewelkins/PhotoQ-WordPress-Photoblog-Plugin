<?php
/**
 * The RO_Option_CheckBoxListItem:: class represents a single check box that goes into above list.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_CheckBoxListItem extends RO_Option_Selectable
{
	function __construct($defaultValue, $label = '', 
					$textBefore = '', $textAfter = '')
	{
		parent::__construct('', $defaultValue, $label, $textBefore, $textAfter);
	}
	
}