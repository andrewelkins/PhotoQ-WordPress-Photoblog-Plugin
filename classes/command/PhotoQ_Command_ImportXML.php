<?php
class PhotoQ_Command_ImportXML extends PhotoQ_Command_PhotoQCommand
{
	public function execute(){
		$this->_photoQReceiver->importXMLFile(esc_attr($_POST['presetFile']));
	}
}