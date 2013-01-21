<?php
/**
 * Null Object of type PhotoQ_Form_Action.
 *
 */
class PhotoQ_Form_NullAction extends PhotoQ_Form_DefaultAction
{
	public function __construct(){
		parent::__construct(new PhotoQ_Command_NullCommand());
	}
	
	public function execute(){}
}