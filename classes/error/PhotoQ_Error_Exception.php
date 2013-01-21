<?php
class PhotoQ_Error_Exception extends Exception
{
	
	/*public function __toString(){
		$msg = '<div class="error">';
		$msg .= $this->getMessage();
		$msg .= '</div>';
		return $msg;
	}*/
	
	public function pushOntoErrorStack(){
		add_settings_error('wimpq-photoq', 'exception', $this->getMessage(), 'error');
	}
}