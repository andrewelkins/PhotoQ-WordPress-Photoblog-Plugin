<?php
class PhotoQ_Command_IncreaseReminderThreshold implements PhotoQ_Command_Executable
{
	public function execute(){
		$cntr = new PhotoQ_Util_ReminderCounter();
		$cntr->increaseThreshold();
	}
}