<?php
class PhotoQ_Util_ManagePageScriptLoader extends PhotoQ_Util_ScriptLoader
{
	public function registerScriptCallbacksWithWordPress($shouldLoadFlashUploader){
		parent::registerScriptCallbacksWithWordPress();
		add_action("admin_print_scripts-$this->_pageHook", array($this, 'injectQueueHandler'), 1);
		if($shouldLoadFlashUploader){
			add_action("admin_print_scripts-$this->_pageHook", array($this, 'injectFlashUploader'), 1);
		}
	}
	
	public function injectQueueHandler(){
		wp_enqueue_script('ajax-queue', plugins_url(PHOTOQ_DIRNAME.'/js/ajax-queue.js'), array('jquery-ui-sortable'),'20080302');
		wp_localize_script('ajax-queue', 'ajaxQueueL10n', array(
			'allowReorder' => current_user_can( 'reorder_photoq' )
		));
	}
	
	public function injectFlashUploader(){
		wp_enqueue_script('swfu-callback', plugins_url(PHOTOQ_DIRNAME.'/js/swfu-callback.js'),array('jquery','swfupload'),'20080217');
		wp_localize_script( 'swfu-callback', 'swfuCallbackL10n', array(
  			'cancelConfirm' => __('Are you sure you want to cancel the upload?', 'PhotoQ'),
			'allUp' => __('All files uploaded.', 'PhotoQ'),
			'select' => __('Select Photos...', 'PhotoQ'),
			'uploading' => __('Uploading', 'PhotoQ'),
			'file' => __('The file', 'PhotoQ'),
			'isZero' => __('has a size of zero.', 'PhotoQ'),
			'invType' => __('has an invalid filetype.', 'PhotoQ'),
			'exceed' => __('exceeds the upload file size limit of', 'PhotoQ'),
			'quotaExceed' => __( 'You have used your space quota. Please delete files before uploading.', 'PhotoQ'),
			'ini' => __('KB in your php.ini config file.', 'PhotoQ'),
			'tooMany' => __('You have attempted to queue too many files.', 'PhotoQ'),
			'queueEmpty' => __('Upload Queue is empty', 'PhotoQ'),
			'addMore' => __('Add more...', 'PhotoQ'),
			'queued' => __('photos queued for upload', 'PhotoQ'),
			'cancelled' => __('cancelled', 'PhotoQ'),
			'progressBarUrl' => plugins_url(PHOTOQ_DIRNAME.'/imgs/progressbar_v12.jpg')
		));
		
		wp_enqueue_script('swfu-uploader', plugins_url(PHOTOQ_DIRNAME.'/js/swfu-uploader.js'),array('swfu-callback'),'20100316');
		wp_localize_script( 'swfu-uploader', 'swfuUploadL10n', array(
  			'uploadUrl' => $this->_getFlashUploadLink(),
			'flashUrl' => includes_url('js/swfupload/swfupload.swf'),
			'fileSizeLimit' => PhotoQHelper::getMaxFileSizeFromPHPINI(),
			'quotaAvailable' => is_multisite() ? get_upload_space_available()/1024 : -1,
			'authCookie' => $this->_getAuthCookie(),
			'loggedInCookie' => $this->_getLoggedInCookie(),
			'nonce' => wp_create_nonce('photoq-uploadBatch'),
			'buttonText' => __('Select Photos...', 'PhotoQ'),
			'buttonImageUrl' => plugins_url(PHOTOQ_DIRNAME.'/imgs/upload.png')
		));
	}
	
	private function _getFlashUploadLink(){
		$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
		return get_bloginfo('wpurl').'/wp-admin/'. $manageMenu->getPageName();
	}
	
	private function _getAuthCookie(){
		return is_ssl() ? $_COOKIE[SECURE_AUTH_COOKIE] : $_COOKIE[AUTH_COOKIE];
	}
	
	private function _getLoggedInCookie(){
		return $_COOKIE[LOGGED_IN_COOKIE];
	}
	
	
	
	
	
}