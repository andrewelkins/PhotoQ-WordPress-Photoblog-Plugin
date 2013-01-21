<?php
class PhotoQ_Util_SettingsPageScriptLoader extends PhotoQ_Util_ScriptLoader
{	
	public function registerScriptCallbacksWithWordPress(){
		parent::registerScriptCallbacksWithWordPress();
		add_action("admin_print_scripts-$this->_pageHook", array($this, 'injectBatchProcessor'), 1);
		$this->_registerOptionControllerScripts();
	}
	
	public function injectBatchProcessor(){
		wp_enqueue_script('batch-progress', plugins_url(PHOTOQ_DIRNAME.'/js/batch-progress.js'), array('jquery'),'20090316');	
		wp_localize_script( 'batch-progress', 'batchProgressL10n', array(
	  		'abortStr' => __('Aborting batch processing due to following error:', 'PhotoQ'),
			'foundErrs' => __('Encountered the following errors:', 'PhotoQ'),
	  		'doneStr' => __('Updating done.', 'PhotoQ'),
			'waitStr1' => _x('Please wait, updating', 'PhotoQ'),
			'waitStr2' => _x('complete.', 'PhotoQ'),
			'progressBarUrl' => plugins_url(PHOTOQ_DIRNAME.'/imgs/progressbar_v12.jpg')
		));
	}
	
	/**
	 * The OptionController Class has its own scripts and stylesheets that need to be injected as well.
	 * This function links them appropriately with WordPress.
	 */
	private function _registerOptionControllerScripts(){
		$optionController = PhotoQ_Option_OptionController::getInstance();
		add_action("admin_print_styles-$this->_pageHook", array($optionController, 'enqueueStyles'), 1);
		add_action("admin_print_scripts-$this->_pageHook", array($optionController, 'enqueueScripts'), 1);
	}
}