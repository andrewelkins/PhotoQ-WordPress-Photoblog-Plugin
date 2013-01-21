<?php
/**
 * Default action to be executed if user doesn't choose any specific action.
 * Default actions do not have any nonces that need to be checked and they are
 * never requested explicitly by the user.
 */
class PhotoQ_Form_DefaultAction extends PhotoQ_Form_Action
{
	public function __construct(PhotoQ_Command_Executable $command){
		parent::__construct('defaultAction', '', $command);
	}
	
	public function checkNonce(){}
	
	public function isRequested(){
		return false;
	}
	
}