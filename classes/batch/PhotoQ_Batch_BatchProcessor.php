<?php
/**
 * Allows for asynchronous execution of the queued commands via Ajax.
 * Allows to break up commands into batches of duration TIME_LIMIT_MS
 * each of which is executed through an Ajax call. This let's us avoid
 * the PHP execution limit if we have procedures that are time consuming.
 * @author flury
 *
 */
class PhotoQ_Batch_BatchProcessor
{
	/**
	 * Execution time limit in milliseconds. BatchProcessor tries to
	 * split commands into chunks of this duration.
	 */
	const TIME_LIMIT_MS = 500;
	
	private $_oc;

	private $_id;
	private $_batchDBTable;
	private $_queuedCommands;
	
	public function __construct($id = NULL){
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
		$this->_id = $id;
		$this->_batchDBTable = new PhotoQ_DB_BatchTable();
		$this->_initializeCommands();
		register_shutdown_function(array($this, 'makeBatchPersistent'));
	}
	
	private function _initializeCommands(){
		if($this->_isRegisteredWithDB())
			$this->_queuedCommands = 
				$this->_batchDBTable->getQueuedBatchCommands($this->_id);
		else
			$this->_queuedCommands = new PhotoQ_Command_BatchMacro();
	}
	
	/**
	 * Indicates whether this BatchProcessor already registered with the
	 * database and got a valid id in return. 
	 * @return boolean
	 */
	private function _isRegisteredWithDB(){
		return !is_null($this->_id);
	}
	
	/**
	 * Write queued commands to database. We need it to be persistent such 
	 * that execution of batch operations can continue at next execution.
	 */
	public function makeBatchPersistent(){
		if($this->_isRegisteredWithDB()){
			$this->_batchDBTable->updateBatch($this->_id, $this->_queuedCommands);
		}
	}
	
	/**
	 * Queue a new command to be executed asynchronously via Ajax.
	 * @param PhotoQ_Command_Batchable $command
	 * @return boolean
	 */
	public function queueCommand(PhotoQ_Command_Batchable $command){
		$this->_queuedCommands->addCommand($command);
		if(!$this->_isRegisteredWithDB()){
			return $this->_registerWithDB();
		}
		return true;
	}
	
	private function _registerWithDB(){
		if($id = $this->_batchDBTable->insertBatch())
			$this->_id = $id;
		else{
			add_settings_error('wimpq-photoq', 'batch-register-failed',
				__('Error when registering batch process: No photos updated.', 'PhotoQ'), 'error');
			return false;
		}
		return true;
	}
	
	/**
	 * Processes the queued commands until the timer expires.
	 * @return float percentage of commands completed
	 */
	public function process(){
		$timer = PhotoQ_Util_Timers::getInstance();	
		while($timer->read('batchProcessing') < self::TIME_LIMIT_MS){
			$this->_queuedCommands->execute();
		}
		if(!$this->_queuedCommands->hasCommands()){
			$this->_delete();
		}
		return $this->_queuedCommands->getPercentageDone();
	}
	
	private function _delete(){
		$this->_id = NULL;
	}
	
	public function getId(){
		return $this->_id;
	}
	
	public function registerFrontend(){
		if($this->_isRegisteredWithDB()){
			$this->_triggerJavaScriptBatchProcessing();
		}
	}
	
	private function _triggerJavaScriptBatchProcessing(){
		?>
			<script type="text/javascript">
				var batchId =  <?php echo $this->getId(); ?>;
				var ajaxNonce = "<?php echo wp_create_nonce( 'photoq-batchProcess' ); ?>";
			</script>
		<?php
	}
	
	public function initBatchToRebuildPublishedPhotos($changedSizes, $updateExif, $changedViews,
						$updateOriginalFolder, $addDelTagFromExifArray, $oldImgDir = ''){
		$db = PhotoQ_DB_DB::getInstance();
		//these two operations should not take too long so do them outside the batch
		$oldNewFolderName = $this->_rebuildFileSystem($updateOriginalFolder, $changedSizes, $oldImgDir);
		if($publishedPhotoIDs = $db->getAllPublishedPhotoIDs()){	
			if($oldImgDir) $updateOriginalFolder = true;
			$batchCommand = new PhotoQ_Command_BatchMacro();
			foreach($publishedPhotoIDs as $id){
				$batchCommand->addCommand(
					new PhotoQ_Command_BatchRebuildPublishedPhoto($id,
						$changedSizes, $updateExif, $changedViews,
						$updateOriginalFolder, $oldNewFolderName,
						$addDelTagFromExifArray[0], $addDelTagFromExifArray[1]
					)
				);
			}
				
			if( $this->queueCommand($batchCommand)){
				if(!empty($changedSizes)){
					add_settings_error('wimpq-photoq', 'batch-update-sizes',
						__('Updating following image sizes:', 'PhotoQ') . ' ' . implode(", ", $changedSizes), 'updated');
				}
				if(!empty($changedViews)){
					add_settings_error('wimpq-photoq', 'batch-update-views',
						__('Updating following views:', 'PhotoQ') . ' ' . implode(", ", $changedViews), 'updated');
				}
				add_settings_error('wimpq-photoq', 'batch-update-published',
					__('Updating all published Photos...', 'PhotoQ'), 'updated');

			}
		
			$this->registerFrontend();
		}
		
	}
	
	
	private function _rebuildFileSystem($updateOriginalFolder, $changedSizes, $oldImgDir){
		
		$oldNewFolderName = new PhotoQ_File_SourceDestinationPair();
		
		if($oldImgDir){
			$this->_moveImgDir($oldImgDir);
			$oldNewFolderName = new PhotoQ_File_SourceDestinationPair($oldImgDir, $this->_oc->getImgDir());
		}elseif($updateOriginalFolder){
			$imgDirs = new PhotoQ_File_ImageDirs();
			$oldNewFolderName = $imgDirs->updateOriginalFolderName($this->_oc->getImgDir(), $this->_oc->getValue('hideOriginals'));
			PhotoQHelper::moveFile($oldNewFolderName);
		}
			
		//remove the image dirs
		foreach ($changedSizes as $changedSize){
			PhotoQHelper::recursiveRemoveDir($this->_oc->getImgDir() . $changedSize . '/');
		}
		PhotoQHelper::debug('oldNewFolderName: ' . print_r($oldNewFolderName,true));
		
		return $oldNewFolderName;
	}
	
	function _moveImgDir($oldImgDir, $includingOriginal = true){
		//move all dirs to the new place
		$imgDirs = new PhotoQ_File_ImageDirs();
		$dirs2move = $imgDirs->getImgDirContent($oldImgDir, $includingOriginal);
		foreach( $dirs2move as $dir2move){
			$moveTo = $this->_oc->getImgDir().basename($dir2move);
			if(!PhotoQHelper::moveFileIfNotExists(new PhotoQ_File_SourceDestinationPair($dir2move, $moveTo))){
				add_settings_error('wimpq-photoq', 'move-error',
					sprintf(__('Unable to move "%1$s" to "%2$s".', 'PhotoQ'), $dir2move, $moveTo), 'error');
			}
		}
		
		//update the watermark directory database entry
		$oldWatermarkPath = get_option( "wimpq_watermark" );
		if($oldWatermarkPath){
			$oldWMFolder = $oldImgDir.'photoQWatermark/';
			$newWMFolder = $this->_oc->getImgDir().'photoQWatermark/';
			$newWatermarkPath = str_replace($oldWMFolder, $newWMFolder, $oldWatermarkPath);
			update_option( "wimpq_watermark", $newWatermarkPath);
		}
	}

}