<?php

class PhotoQ_Form_PostAction extends PhotoQ_Form_Action
{
	public function isRequested(){
		return isset($_POST[$this->_name]);
	}
	
	public function checkNonce(){
		check_admin_referer($this->_nonceName, $this->_nonceName);
	}
}