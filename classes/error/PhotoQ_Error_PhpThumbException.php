<?php
class PhotoQ_Error_PhpThumbException extends PhotoQ_Error_Exception
{
	
	public function __construct($debugMessages, $fatalErrorMessage = ''){
		$this->message = __('PhpThumb failed:', 'PhotoQ') . '<pre>' .
			$fatalErrorMessage . '\n\n' .
			implode('\n\n', $debugMessages) . '</pre>';
	}
}