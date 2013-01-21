<?php
/**
 * Represents an action requested by the user via a click on the HTML form.
 * Every action is identified by the name of the submit button or link action.
 * For security reasons, every action also has an associated nonce that is 
 * submitted together with the request. Finally, it holds a list of commands that
 * we execute once we get the request.
 * @author manu
 *
 */
abstract class PhotoQ_Form_Action
{
	protected $_name;
	protected $_nonceName;
	private $_command;
	
	public function __construct($name, $nonceName, PhotoQ_Command_Executable $command){
		$this->_name = $name;
		$this->_nonceName = $nonceName;
		$this->_command = $command;
	}
	
	/**
	 * Calls the appropriate WordPress check_admin_referer function
	 * in order to check validity of the nonce.
	 */
	abstract public function checkNonce();
	
	/**
	 * Determines whether this action was requested by the user.
	 * @return boolean
	 */
	abstract public function isRequested();
	
	public function execute(){
		$this->_command->execute();
	}

}