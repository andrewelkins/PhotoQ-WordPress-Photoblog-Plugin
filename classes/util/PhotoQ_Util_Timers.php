<?php
/**
 * Shamelessly copied from Drupal. This gives us a set of timers that we for now use
 * in the batch processing stuff to prevent script timeouts.
 * Usage:
 *
 * $timer = PhotoQSingleton::getInstance('PhotoQ_Util_Timers');
 * $timer->start('batchProcessing');
 * if($timer->read('batchProcessing') < 1000) ...
 *
 * @author manu
 *
 */
class PhotoQ_Util_Timers implements PhotoQSingleton
{
	/**
	 * @var Array of registered timers
	 */
	private $_timers = array();
	
	private static $_singletonInstance;
	
	private function __construct(){}
		
	public static function getInstance()
	{
		if (!isset(self::$_singletonInstance)) {
			self::$_singletonInstance = new self();
		}
		return self::$_singletonInstance;
	}
	

	/**
	 * Start the timer with the specified name. If you start and stop
	 * the same timer multiple times, the measured intervals will be
	 * accumulated.
	 *
	 * @param name
	 *   The name of the timer.
	 */
	function start($name) {

		list($usec, $sec) = explode(' ', microtime());
		$this->_timers[$name]['start'] = (float)$usec + (float)$sec;
		$this->_timers[$name]['count'] = isset($this->_timers[$name]['count']) ? ++$this->_timers[$name]['count'] : 1;
	}

	/**
	 * Read the current timer value without stopping the timer.
	 *
	 * @param name
	 *   The name of the timer.
	 * @return
	 *   The current timer value in ms.
	 */
	function read($name) {

		if (isset($this->_timers[$name]['start'])) {
			list($usec, $sec) = explode(' ', microtime());
			$stop = (float)$usec + (float)$sec;
			$diff = round(($stop - $this->_timers[$name]['start']) * 1000, 2);

			if (isset($this->_timers[$name]['time'])) {
				$diff += $this->_timers[$name]['time'];
			}
			return $diff;
		}
	}

	/**
	 * Stop the timer with the specified name.
	 *
	 * @param name
	 *   The name of the timer.
	 * @return
	 *   A timer array. The array contains the number of times the
	 *   timer has been started and stopped (count) and the accumulated
	 *   timer value in ms (time).
	 */
	function stop($name) {
		$this->_timers[$name]['time'] = $this->read($name);
		unset($this->_timers[$name]['start']);

		return $this->_timers[$name];
	}


}