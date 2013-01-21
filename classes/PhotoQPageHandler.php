<?php
abstract class PhotoQPageHandler
{
	protected $_oc;
	protected $_db;
	protected $_formActions = array();
	protected $_defaultFormAction;
	protected $_defaultPanel = '';
	
	public function __construct(){
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
		$this->_db = PhotoQ_DB_DB::getInstance();
		$this->_defaultFormAction = new PhotoQ_Form_NullAction();
	}
	
	/**
	 * sink function for the 'add_submenu_page' hook.
	 * displays the page content for the 'Manage PhotoQ' submenu
	 */
	public final function handle()
	{
		$this->_initialize();
		$action = $this->_getRequestedAction();
		$action->checkNonce();
		$action->execute();		
	}
	
	protected function _initialize(){
		$this->_registerFormActions();
		$this->_createDirIfNotExists($this->_oc->getCacheDir(), true);
	}
	
	/**
	 * To register every possible user action, which is a result of a submit button or link pressed on the HTML form,
	 * we link every action with the commands that should be executed if the action was selected by the user.
	 */
	private function _registerFormActions(){
		$this->_defaultFormAction = $this->_createDefaultAction();
		$this->_formActions = $this->_createActionArray(); 
	}
	
	abstract protected function _createDefaultAction();
	abstract protected function _createActionArray();
	
	protected function _getRequestedAction(){
		foreach($this->_formActions as $action)
			if($action->isRequested()) return $action;
		return $this->_defaultFormAction;
	}
	
	abstract public function preparePanel();
	
	public final function showPanel($panel){
		require_once(PHOTOQ_PATH.'panels/'.$panel);
	}
	
	protected function _buildMacroCommandShowingDefaultPanel(
		array $commands = array()
	){
		$macroCommand = new PhotoQ_Command_Macro($commands);
		$macroCommand->addCommand(new PhotoQ_Command_PreparePanel($this));
		$macroCommand->addCommand(
			new PhotoQ_Command_ShowPanel($this, $this->_defaultPanel)
		);
		return $macroCommand;
	}

	/**
	 * Creates directory with path given if it does not yet exist. If an error occurs it
	 * is displayed.
	 *
	 * @param string $dir	The path of the directory to be created.
	 */
	protected function _createDirIfNotExists($dir, $silent=false){
		//create $dir if does not exist yet
		if( !PhotoQHelper::createDir($dir) && !$silent)
			add_settings_error('wimpq-photoq', 'dir-not-created',
					sprintf(__('Error when creating "%s" directory. Please check your PhotoQ settings.', 'PhotoQ'), $dir), 'error');
	}
	
}