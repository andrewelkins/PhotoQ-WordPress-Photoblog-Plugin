<?php
abstract class PhotoQ_Command_PhotoQCommand implements PhotoQ_Command_Executable
{
	protected $_photoQReceiver;
	
	public function __construct(PhotoQPageHandler $receiver){
		$this->_photoQReceiver = $receiver;
	}
}