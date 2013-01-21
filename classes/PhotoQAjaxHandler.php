<?php
class PhotoQAjaxHandler implements PhotoQHookable
{
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('wp_ajax_photoq_reorder', 
			array($this, 'actionAjaxReorderQueue'));
		add_action('wp_ajax_photoq_edit', 
			array($this, 'actionAjaxEditQueueEntry'));
		add_action('wp_ajax_photoq_batchProcessing', 
			array($this, 'actionAjaxBatchProcessing'));
	}
	
	/**
	 * Common initialization stuff to be done at beginning 
	 * of each Ajax Call.
	 */
	private function _initAjaxCall(){
		PhotoQHelper::debug('got ajax call');
		
		if ( !is_user_logged_in() )
			die('-1');
		
		PhotoQHelper::debug('queue initialized');
		
		foreach( $_POST as $key => $value)
			PhotoQHelper::debug("POST $key: $value");
	}
	
	/**
	 * Callback for Ajax Call that reorders queue.
	 * @return unknown_type
	 */
	public function actionAjaxReorderQueue(){
		
		$this->_initAjaxCall();
				
		check_ajax_referer( 'queueReorder', 'queueReorderNonce' );
		
		if(!current_user_can( 'reorder_photoq' ))
			die(__('You do not have sufficient privileges to perform this task', 'PhotoQ'));
			
		PhotoQHelper::debug('reordering queue');
		PhotoQHelper::debug(sizeof($_POST['photoq']));
		
		//get length of queue and check that both arrays have same size
		$queue = PhotoQQueue::getInstance();
		$qLength = $queue->getLength();
		sizeof($_POST['photoq']) == $qLength or die('1');
			
		PhotoQHelper::debug('sanity check passed');
		
		$qTable = new PhotoQ_DB_QueueTable();
		for($i=0; $i<$qLength; $i++){
			$currentPhoto = $queue->getQueuedPhoto($i);
			if( $_POST['photoq'][$i] != $currentPhoto->getId() ){
				PhotoQHelper::debug('reordering');
				$qTable->setQueuePosition(esc_attr($_POST['photoq'][$i]), $i);		
			}
		}
			
		die();
	}
	
	
	/**
	 * Callback for Ajax Call that edits queue entry.
	 * @return unknown_type
	 */
	public function actionAjaxEditQueueEntry(){
	
		global $current_user;
		$this->_initAjaxCall();
		$queue = PhotoQQueue::getInstance();
		try{
			$photoToEdit = $queue->getQueuedPhotoById(esc_attr($_POST['id']));	
		}catch(PhotoQ_Error_PhotoNotFoundException $e){
			die($e->getMessage());
		}
		//the user tries do something he is not allowed to do
		if ( $current_user->id != $photoToEdit->getAuthor() &&  !current_user_can('edit_others_posts') ){
			die(__('You do not have sufficient privileges to perform this task', 'PhotoQ'));	
		}
		
		
		PhotoQHelper::debug('starting ajax editing');

		?>
			<form method="post" enctype="multipart/form-data" action="<?php $manageMenu = new PhotoQ_Util_ManageAdminMenuLocation(); echo $manageMenu->getPageName(); ?>">	
				<div class="photo_info">
			
		<?php 
		PhotoQHelper::debug('started form');
			if ( function_exists('wp_nonce_field') )
				wp_nonce_field('photoq-saveBatch','photoq-saveBatch');
		PhotoQHelper::debug('passed nonce');				
			$photoToEdit->showPhotoEditForm();		
		PhotoQHelper::debug('showed photo');	
		?>
			
					<div class="submit">
						<input id="saveBatch" type="submit" class="button-primary submit-btn" name="save_batch" value="<?php _e('Save Changes', 'PhotoQ') ?>" />
						<input type="submit" class="button-secondary submit-btn" onclick="window.location = window.location.href;" 
						value="<?php _e('Cancel', 'PhotoQ') ?>" />
					</div>
				</div>
			</form>
		
		<?php
		PhotoQHelper::debug('form over');
		die();
	}
	
	/**
	 * Callback for Ajax batch processing.
	 * @return unknown_type
	 */
	public function actionAjaxBatchProcessing(){
		$this->_initAjaxCall();
		check_ajax_referer( "photoq-batchProcess" );
		
		PhotoQHelper::debug('starting batch with id: '. $_POST['id']);
		$percentageCompleted = $this->_executeBatch($_POST['id']);
		PhotoQHelper::debug('executed');
		
		
		$this->_communicateCompletionStatusToJavaScript($percentageCompleted, $this->_showErrorsIfAny());
		
		die();
	}
	
	/**
	 * Process previously stored BatchSets
	 * @param $id integer	The id of the batch to be executed
	 * @return float percentage done.
	 */
	private function _executeBatch($id){
		$timer = PhotoQ_Util_Timers::getInstance();
		$timer->start('batchProcessing');
		$bp = new PhotoQ_Batch_BatchProcessor($id);
		return $bp->process();
	}
	
	private function _showErrorsIfAny(){
		$output = '';
		$settings_errors = get_settings_errors('wimpq-photoq');
		if(is_array($settings_errors)){
			foreach ( $settings_errors as $key => $details ) {
				$output .= $details['message'] . '<br/><br/>';
			}
		}
		return $output;
	}
	
	/*
	 * Prints the JSON to answer the Ajax request.
	 */
	private function _communicateCompletionStatusToJavaScript($percentageCompleted, $errMsg){
		echo '{"percentage": "'. 100*$percentageCompleted .'", "errMsg": "'. addslashes($errMsg) .'"}';
	}
	
}