<?php
class PhotoQ_File_SourceDestinationPair
{
	private $_source;
	private $_destination;
	
	public function __construct($source = '', $destination = ''){
		$this->_source = $source;
		$this->_destination = $destination;	
	}
	
	public function getSource(){
		return $this->_source;
	}
	
	public function getDestination(){
		return $this->_destination;
	}
	
	public function sourceExists(){
		return file_exists($this->_source);
	}
	
	public function destinationExists(){
		return file_exists($this->_destination);
	}
}