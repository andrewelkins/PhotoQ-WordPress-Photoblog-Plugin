<?php

class PhotoQ_Form_GetAction extends PhotoQ_Form_Action
{
	public function isRequested(){
		return isset($_GET['action']) && $_GET['action'] == $this->_name;
	}
	
	public function checkNonce(){
		check_admin_referer($this->_nonceName . esc_attr($_GET['id']));
	}
}