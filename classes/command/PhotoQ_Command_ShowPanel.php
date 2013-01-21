<?php
class PhotoQ_Command_ShowPanel extends PhotoQ_Command_PhotoQCommand
{
	
	private $_panel;
	
	public function __construct($receiver, $panel){
		parent::__construct($receiver);
		$this->_panel = $panel;
	}
	
	public function execute(){
		$this->_photoQReceiver->showPanel($this->_panel);
	}
}