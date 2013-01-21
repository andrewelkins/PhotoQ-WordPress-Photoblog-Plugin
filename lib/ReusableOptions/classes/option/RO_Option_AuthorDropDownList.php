<?php
/**
 * @package ReusableOptions
 */
 

/**
 * A RO_Option_AuthorDropDownList:: is a container for RO_Option_DropDownItems. Only one of which
 * can be selected at a time.
 *
 * @author  M.Flury
 * @package ReusableOptions
 */
class RO_Option_AuthorDropDownList extends RO_Option_DropDownList
{

	/**
	 * PHP5 type constructor
	 */
	function __construct($name, $defaultValue = '', $label = '', 
					$textBefore = '', $textAfter = '')
	{
		global $wpdb;
		/** @todo Move select to get_authors(). */
		$authors = $wpdb->get_results("SELECT ID, user_nicename from $wpdb->users ORDER BY display_name");

		parent::__construct($name, $defaultValue, $label, $textBefore, $textAfter);
		
		//populate the list with all registered authors
		foreach($authors as $author){
			$this->addChild(
				new RO_Option_DropDownItem(
				 $author->user_nicename,
				 $author->ID
				)
			);	
		}
		
	}
	
	

}


