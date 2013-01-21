<?php

class PhotoQ_Option_RoleOption extends RO_Option_CapabilityCheckBoxList
{

	function __construct($name, $role = 'administrator', $defaultValue = '', $label = '',
				$textBefore = '', $textAfter = '')
	{
		parent::__construct($name, $role, $defaultValue, $label, $textBefore, $textAfter);
		
		$this->addChild(
			new RO_Option_CheckBoxListItem(
				'use_primary_photoq_post_button',
				__('Allowed to use primary post button','PhotoQ'),
				'<li>',
				'</li>'
			)
		);
		$this->addChild(
			new RO_Option_CheckBoxListItem(
				'use_secondary_photoq_post_button',
				__('Allowed to use secondary post button','PhotoQ'),
				'<li>',
				'</li>'
			)
		);
		$this->addChild(
			new RO_Option_CheckBoxListItem(
				'reorder_photoq',
				__('Allowed to reorder queue','PhotoQ'),
				'<li>',
				'</li>'
			)
		);
		
	}

		
}		


