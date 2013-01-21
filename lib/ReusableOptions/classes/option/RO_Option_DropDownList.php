<?php
/**
 * @package ReusableOptions
 */


/**
 * A RO_Option_DropDownList:: is a container for RO_Option_DropDownItems. Only one of which
 * can be selected at a time.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_DropDownList extends RO_Option_SelectionList
{

	
	
	/**
	 * Populate List with children given by name-value array.
	 *
	 * @access public
	 * @param array $nameValueArray Name-value pairs with which to populate the list.
	 */
	function populate($nameValueArray)
	{
		//populate the list with all child options
		foreach ($nameValueArray as $name => $value){
			$this->addChild(
			new RO_Option_DropDownItem(
			$name, $value
			)
			);
		}
	}



}