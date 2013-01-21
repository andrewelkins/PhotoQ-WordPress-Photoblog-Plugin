<?php
class PhotoQ_Util_ReminderCounter
{
	const COUNTER_OPTION_NAME = 'wimpq_posted_since_reminded';
	const THRESHOLD_OPTION_NAME = 'wimpq_reminder_threshold';
	const LAST_RESET_OPTION_NAME = 'wimpq_last_reminder_reset';
	const MIN_THRESHOLD = 50;
	const SECONDS_PER_DAY = 86400;
	const SECONDS_IN_ONE_HUNDRED_DAYS = 8640000;
	
	private $_postedSinceLastReminder;
	
	public function __construct(){
		$this->_postedSinceLastReminder = get_option(self::COUNTER_OPTION_NAME);
		if($this->_postedSinceLastReminder == NULL)
			$this->_init();
	}
	
	private function _init(){
		$this->_postedSinceLastReminder = 0;
		add_option(self::COUNTER_OPTION_NAME, 0);
		add_option(self::THRESHOLD_OPTION_NAME, MIN_THRESHOLD);
		add_option(self::LAST_RESET_OPTION_NAME, time());
	}
	
	public function reset(){
		update_option(self::COUNTER_OPTION_NAME, 0);
		update_option(self::LAST_RESET_OPTION_NAME, time());
	}
	
	public function increment(){
		$this->_postedSinceLastReminder++;
		update_option(self::COUNTER_OPTION_NAME, $this->_postedSinceLastReminder);
	}
	
	/**
	 * To not bother guys who donated often, we increase the threshold 
	 * exponentially every time the user states that he donated already.
	 */
	public function increaseThreshold(){
		if($this->_hasTimeElapsed(self::SECONDS_PER_DAY))
			update_option(self::THRESHOLD_OPTION_NAME, 2*$this->_getThreshold());
	}
	
	private function _hasTimeElapsed($time){
		$then = get_option(self::LAST_RESET_OPTION_NAME);
		$now = time();
		return $now - $then > $time; 
	}
	
	private function _getThreshold(){
		$thld = get_option(self::THRESHOLD_OPTION_NAME);
		if($thld < self::MIN_THRESHOLD)
			$thld = self::MIN_THRESHOLD;
		return $thld;
	}
	
	/**
	 * If more than threshold photos have been posted since the last time the reminder 
	 * has been shown and if more than 100 days have elapsed since then, the reminder 
	 * should be shown.
	 * @return boolean
	 */
	public function shouldShowReminder(){
		return $this->_isAboveThreshold() && $this->_hasTimeElapsed(self::SECONDS_IN_ONE_HUNDRED_DAYS);
	}
	
	private function _isAboveThreshold(){
		return $this->_postedSinceLastReminder > $this->_getThreshold();
	}
	
}