<?php
/**
 * @package ReusableOptions
 */
 

/**
 * A RO_Option_RadioButtonList:: is a container for RO_Option_RadioButtons. Only one of which
 * can be selected at a time.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_RadioButtonList extends RO_Option_SelectionList
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
		if(is_a($option, 'RO_Option_RadioButton')){
			//all radiobuttons in a group must have the name of the group
			$option->setOptionName($this->getName());		
			return parent::addChild($option);
		}
		
		return false;
	}
	
	
	
	/**
	 * Populate List with children given by value-label array.
	 *
	 * @access public
	 * @param array $nameValueArray value-label pairs with which to populate the list.
	 */
	function populate($valueLabelArray, $textBefore = '', $textAfter = '')
	{
		//populate the list with all ImageSizes
		foreach ($valueLabelArray as $value => $label){
			$this->addChild(
			new RO_Option_RadioButton(
			$value, $label, $textBefore, $textAfter
			)
			);
		}
	}
	
	

}


