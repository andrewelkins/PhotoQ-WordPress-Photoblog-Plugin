<?php
abstract class PhotoQ_Command_DatabaseCommand implements PhotoQ_Command_Executable
{
	protected $_db;
	
	public function __construct(PhotoQ_DB_DB $db){
		$this->_db = $db;
	}
	
}