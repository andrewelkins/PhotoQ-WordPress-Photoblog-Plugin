<?php
/**
 * @package ReusableOptions
 */
 

/**
 * A RO_Option_CheckBoxList:: is a container for RO_Option_CheckBoxListItems. Several of which
 * can be selected at a time.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_CheckBoxList extends RO_Option_SelectionList
{

	/**
	 * Add an option to the composite.	
	 * 
	 * @param object RO_Option_ReusableOption $option  The option to be added to the composite.
	 * @return boolean	True if options could be added (composite), false otherwise.
	 * @access public
	 */
	function addChild($option)
	{	
		if(is_a($option, 'RO_Option_CheckBoxListItem')){
			//all checkboxes in a list must have the name of the group
			$option->setOptionName($this->getName());		
			return parent::addChild($option);
		}
		
		return false;
	}
	
	

}