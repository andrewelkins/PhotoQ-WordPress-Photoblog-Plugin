<?php
class PhotoQAdminPages implements PhotoQHookable
{	
	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('admin_menu', array($this, 'actionSetupAdminPages'));
	}
	
	/**
	 * PhotoQ uses two admin pages, one to manage the queue and one to handle its settings.
	 * For each of these pages, page setup consists of adding the page to WordPress' menu
	 * structure and of registering any needed JS Scripts of CSS stylesheets with WordPress.
	 */
	public function actionSetupAdminPages()
	{
		$this->_setupManagePage();
		$this->_setupSettingsPage();	
	}
	
	private function _setupManagePage(){
		$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
		$pageHook = add_submenu_page($manageMenu->getParentMenu(), __('Manage PhotoQ', 'PhotoQ'), 
			'PhotoQ', 'access_photoq', PhotoQ_Util_AdminMenuLocation::PLUGIN_MENU_SLUG, 
			array(new PhotoQManagePageHandler(), 'handle')
		);
		$scriptLoader = new PhotoQ_Util_ManagePageScriptLoader($pageHook);
		$scriptLoader->registerScriptCallbacksWithWordPress($this->_shouldLoadFlashUploader());
	}
	
	private function _shouldLoadFlashUploader(){
		$oc = PhotoQ_Option_OptionController::getInstance();
		return $oc->getValue('enableBatchUploads') && 
			( isset($_POST['add_entry']) || isset($_POST['update_photos']) );
	}
	
	private function _setupSettingsPage(){
		$pageHook = add_options_page(__('PhotoQ Options','PhotoQ'), 
			'PhotoQ', 'manage_photoq_options', PhotoQ_Util_AdminMenuLocation::PLUGIN_MENU_SLUG, 
			array(new PhotoQSettingsPageHandler(), 'handle')
		);
		$scriptLoader = new PhotoQ_Util_SettingsPageScriptLoader($pageHook);
		$scriptLoader->registerScriptCallbacksWithWordPress();
	}

	
}