<?php
/**
 * This class is responsible for displaying the PhotoQ Dashboard Widget.
 * The PhotoQ Dashboard Widget shows the three first photos in the queue and 
 * provides two buttons for easy access to PhotoQ from the Dashboard.
 */
class PhotoQDashboard implements PhotoQHookable
{
	const MAX_NUMBER_PHOTOS_SHOWN = 3;
	private $_thumbDimension;
	private $_queue;
	
	public function __construct(PhotoQ_Photo_Dimension $thumbDimension){
		$this->_thumbDimension = $thumbDimension;
	}
	
	/**
	 * To hook the appropriate callback functions (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('wp_dashboard_setup', array($this, 'actionAddDashboardWidget') );
		add_action('admin_print_styles-index.php', 
			array($this, 'actionInjectDashboardCSS'), 1);
	}
	
	public function actionAddDashboardWidget() {
		if(current_user_can('access_photoq'))
			wp_add_dashboard_widget('dashboard_photoq', 'PhotoQ', array($this,'display'));
	}
	
	public function actionInjectDashboardCSS()
	{
		wp_enqueue_style('photoq-dashboard', 
			plugins_url(PHOTOQ_DIRNAME.'/css/photoq-dashboard.css'));	
	}

	/**
	 * Prints the PhotoQ dashboard widget as formatted HTML.
	 */
	public function display(){
		PhotoQHelper::debug('enter PhotoQDashboard::display()');
		$this->_initializeQueue();
		PhotoQHelper::debug('PhotoQDashboard::display(): queue intialized');
		$this->_displayQueueStatus();
		$this->_displayQueue();
		$this->_displayButtons();
		PhotoQHelper::debug('leave PhotoQDashboard::display()');	
	}
	
	private function _initializeQueue(){
		if(!isset($this->_queue))//lazy initialization, creating queue
			$this->_queue = PhotoQQueue::getInstance();
	}
	
	private function _displayQueueStatus(){
		$this->_printNumberOfQueuedPhotos($this->_queue->getLength());
	}
	
	private function _printNumberOfQueuedPhotos($queueLength){
		printf(__('Number of photos in the queue: %s', 'PhotoQ'), 
			"<b>$queueLength</b>");
	}
	
	private function _displayQueue(){
		if(!$this->_queue->isEmpty()){
			$this->_printQueueHeading();
			$this->_printQueueTable();
		}
	}
	
	private function _printQueueHeading(){
		echo '<h5>' . __('Next Photos to be published', 'PhotoQ').':</h5>';
	}

	private function _printQueueTable(){
		$this->_printStartOfTable(); 
		$this->_printTableRows();
		$this->_printEndOfTable();
	}
	
	private function _printStartOfTable(){
		echo '<div class="table"><table><tbody>';
	}
	
	private function _printEndOfTable(){
		echo '</tbody></table></div>';
	}
	
	private function _printTableRows(){
		$numberOfRows = $this->_getNumberOfPhotosToDisplay();
		for ($i = 0; $i < $numberOfRows; $i++){
			$this->_printRow($i);
		}
	}
	
	private function _getNumberOfPhotosToDisplay(){
		return min(
			PhotoQDashboard::MAX_NUMBER_PHOTOS_SHOWN, 
			$this->_queue->getLength()
		);
	}
	
	private function _printRow($rowNumber){
		if($rowNumber == 0)
			$this->_printStartOfFirstRow();
		else
			$this->_printStartOfRow();
		$this->_printImgInformation($rowNumber);
		$this->_printEndOfRow();
	}
	
	private function _printStartOfFirstRow(){
		echo '<tr class="first">';
	}
	
	private function _printStartOfRow(){
		echo '<tr>';
	}
	
	private function _printEndOfRow(){
		echo '</tr>';
	}
	
	private function _printImgInformation($rowNumber){
		$photo = $this->_queue->getQueuedPhoto($rowNumber);
		echo '<td>'.$photo->getAdminThumbImgTag($this->_thumbDimension).'</td>';
		echo '<td>'.$photo->getTitle().'</td>';
	}
	
	private function _displayButtons(){
		$this->_printStartOfForm();
		if(!$this->_queue->isEmpty()){
			$this->_printEditQueueButton();
		}
		$this->_printAddPhotoButton();
		$this->_printEndOfForm();
	}
	
	private function _printStartOfForm(){
		$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
		echo '<form method="post" action="'.$manageMenu->getPageName().'">';
	}
	
	private function _printEndOfForm(){
		echo '</form>';
		echo '<br class="clear"/>';
	}
	
	private function _printEditQueueButton(){
		echo '<input type="submit" class="button" name="show"
				value="'. __('Edit Queue', 'PhotoQ').'" />';
	}
	
	private function _printAddPhotoButton(){
		wp_nonce_field('photoq-manageQueue', 'photoq-manageQueue');
		echo '<input type="submit" class="button-primary action" name="add_entry"
			value="'. __('Add Photos to Queue', 'PhotoQ').'" />';
	}
}