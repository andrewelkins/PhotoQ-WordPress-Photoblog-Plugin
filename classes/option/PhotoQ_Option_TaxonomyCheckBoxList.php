<?php
/**
 * @package ReusableOptions
 */


/**
 * A PhotoQ_Option_TaxonomyCheckBoxList:: includes as items a list of registered taxonomies.
 *
 * @author  M.Flury
 * @package PhotoQ
 */
class PhotoQ_Option_TaxonomyCheckBoxList extends RO_Option_CheckBoxList
{
	
	public function __construct($name, $defaultValue = '', $label = '',
				$textBefore = '', $textAfter = '')
	{
		parent::__construct($name, $defaultValue, $label, $textBefore, $textAfter);
	}
	
	public function populate(){
		$taxonomies = array('category');
		$taxonomies = array_merge($taxonomies, PhotoQ_Util_Taxonomies::getCustomTaxonomies());
		foreach($taxonomies as $taxonomy){
			$this->addChild(
				new RO_Option_CheckBoxListItem(
					$taxonomy, 
					$taxonomy,
					'<li>',
					'</li>'
				)
			);
		}
	}
	
	
	
	
	
}



