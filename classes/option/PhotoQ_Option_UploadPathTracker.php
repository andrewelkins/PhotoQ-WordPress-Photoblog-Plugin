<?php
/**
 * This class is responsible for keeping PhotoQ posts in line with the WP upload path setting.
 */
class PhotoQ_Option_UploadPathTracker implements PhotoQHookable
{
	/**
	 * The name of the transient we store to pass option values around between the options and
	 * the options-media page.
	 * @var unknown_type
	 */
	const TRANSIENT_NAME = 'wimpq_upload_path_changed';
	
	const MEDIA_SETTINGS_PAGE_HOOK = 'options-media.php';
	/**
	 * The path before the setting changed
	 * @var string
	 */
	private $_oldPath = '';
	
	/**
	 * The path after the setting changed
	 * @var string
	 */
	private $_newPath = '';
	
	/**
	 * To hook the appropriate callback functions (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('update_option_upload_path', array($this, 'actionUploadPathChanged'), 10, 2);
		add_action('admin_head-'.self::MEDIA_SETTINGS_PAGE_HOOK, array($this, 'actionPrepareForBatchRebuild'));
		$scriptLoader = new PhotoQ_Util_SettingsPageScriptLoader(self::MEDIA_SETTINGS_PAGE_HOOK);
		$scriptLoader->registerScriptCallbacksWithWordPress();
	}
	
	/**
	 * Callback function called when option did change. If it did we set a transient since
	 * the options page redirects to the options-media page. We can then retrieve the transient
	 * on the options-media page to take appropriate actions there.
	 * @param string $oldValue
	 * @param string $newValue
	 */
	public function actionUploadPathChanged($oldValue, $newValue) {
		set_transient(self::TRANSIENT_NAME, array($oldValue, $newValue), 30);
	}
	
	
	public function actionPrepareForBatchRebuild(){
		if($this->_retrieveChangedValues()){
			$bp = new PhotoQ_Batch_BatchProcessor();
			$oc = PhotoQ_Option_OptionController::getInstance();
			
			$bp->initBatchToRebuildPublishedPhotos(array(), false, $oc->getViewNames(),
				false, array(array(), array()), $this->_oldPath);
		}
	}
	
	/**
	 * Retrieves the transient and returns whether it succeeded in doing so (if the option 
	 * changed) or not (if there was no transient because the option stayed the same).
	 */
	private function _retrieveChangedValues(){
		if(!list($this->_oldPath, $this->_newPath) = get_transient(self::TRANSIENT_NAME))
			return false;
		delete_transient(self::TRANSIENT_NAME);
		$this->_oldPath = $this->_sanitizePath($this->_oldPath);
		$this->_newPath = $this->_sanitizePath($this->_newPath);
		return true;
	}
	
	private function _sanitizePath($path){
		if ( empty($path) ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} else {
			$dir = $path;
			if ( 'wp-content/uploads' == $path ) {
				$dir = WP_CONTENT_DIR . '/uploads';
			} elseif ( 0 !== strpos($dir, ABSPATH) ) {
				// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
				$dir = path_join( ABSPATH, $dir );
			}
		}
		$dir = str_replace('\\\\', '\\', $dir);
		$dir = str_replace('\\','/',$dir);
		return rtrim($dir, '/\\') . '/';
	}
}