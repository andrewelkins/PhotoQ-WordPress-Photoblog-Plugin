<?php
class PhotoQ_Command_SaveOptions extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_updateController();
		add_settings_error('wimpq-photoq', 'settings-saved', __('Options saved.', 'PhotoQ'), 'updated');
	}
	
	private function _updateController(){
		$oc = PhotoQ_Option_OptionController::getInstance();
		$oc->update();
	}
	
}