<?php
abstract class PhotoQ_File_Importer
{
	private $_destinationDir;
	
	public function __construct($destinationDir){
		$this->setDestinationDir($destinationDir);
	}	
	
	abstract public function import();
	
	public function getDestinationDir(){
		return $this->_destinationDir;
	}
	
	public function setDestinationDir($destinationDir){
		$this->_destinationDir = $this->_sanitizeDestinationDir($destinationDir);
	}
		
	private function _sanitizeDestinationDir($destinationDir){
		$destinationDir = rtrim($destinationDir,'/\\');
		//if on windows backslashes need to be there otherwise wp upload function is choking.
		//we really need to find a better solution for this.
		$cleanAbs = str_replace('\\', '/', ABSPATH);
		return str_replace($cleanAbs, ABSPATH, $destinationDir);
	}
	
}