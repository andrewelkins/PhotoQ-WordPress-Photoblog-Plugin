<?php
/**
 * Creates all the components needed by PhotoQ and hooks them up with WordPress.
 * @author  M.Flury
 * @package PhotoQ
 */
class PhotoQ
{
	const VERSION = '2.0b6';
	
	public function __construct()
	{
		PhotoQHelper::debug('-----------start plugin-------------');
		
		$handlers = $this->_createWordPressCallbackHandlers();
		$this->_registerHandlersWithWordPress($handlers);
				
		//PhotoQHelper::debug(print_r($_REQUEST,true));
		
		PhotoQHelper::debug('leave __construct()');
	}
	
	
	private function _createWordPressCallbackHandlers(){
		$handlers = array();
		$handlers[] = new PhotoQAdminPages();
		
		$adminThumbDimension = $this->_determineAdminThumbDimension();
		
		$handlers[] = new PhotoQDashboard($adminThumbDimension);
		$handlers[] = new PhotoQEditPostsDisplay($adminThumbDimension);
		
		$handlers[] = new PhotoQAjaxHandler();
		$handlers[] = new PhotoQCustomRequestHandler();
		
		$handlers[] = new PhotoQ_Util_Upgrader();
		$handlers[] = new PhotoQContextualHelp();
		$handlers[] = new PhotoQFavoriteActions();

		$handlers[] = new PhotoQ_Option_UploadPathTracker();
		
		$handlers[] = new PhotoQLocalization();
		
		
		$handlers[] = new PhotoQWordPressEditor();
		$handlers[] = new PhotoQ_Util_GarbageCollector();
		
		$handlers[] = new PhotoQQueuedPostType();
		
		return $handlers;
	}
	
	private function _determineAdminThumbDimension(){
		$oc = PhotoQ_Option_OptionController::getInstance();
		return new PhotoQ_Photo_Dimension(
			$oc->getValue('showThumbs-Width'),
			$oc->getValue('showThumbs-Height')
		);
	}
	
	private function _registerHandlersWithWordPress(array $handlers){
		foreach($handlers as $handler){
			$this->_hookIntoWordPress($handler);
		}
	}
	
	private function _hookIntoWordPress(PhotoQHookable $handler){
		$handler->hookIntoWordPress();	
	}

	/**
	 * Called by cronjob file. Allows automatic publishing of top photos
	 * in queue via cronjob. Can be replaced by the custom request handler 'cronjob'.
	 */
	public function cronjob()
	{	
		PhotoQHelper::debug('enter cronjob()');
		$queue = PhotoQQueue::getInstance();
		$queue->publishViaCronjob();
		PhotoQHelper::debug('leave cronjob()');
	}

}