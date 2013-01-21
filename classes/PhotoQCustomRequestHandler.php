<?php
class PhotoQCustomRequestHandler implements PhotoQHookable
{
	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('parse_request', 
			array($this, 'actionCustomRequestHandler'));
		add_filter('query_vars', 
			array($this, 'filterRegisterQueryVars'));
	}
	
	
	/**
	 * Register query vars needed for special request handling.
	 * See http://willnorris.com/2009/06/wordpress-plugin-pet-peeve-2-direct-calls-to-plugin-files
	 */
	function filterRegisterQueryVars($query_vars) {
    	$query_vars[] = 'photoQHandler';
    	return $query_vars;
	}
	
	/**
	 * Hook for special request handling.
	 * We use WP request handling instead of including all the wp functions to bootstrap.
	 * See http://willnorris.com/2009/06/wordpress-plugin-pet-peeve-2-direct-calls-to-plugin-files
	 * @param $wp
	 * @return unknown_type
	 */
	function actionCustomRequestHandler($wp){
		// process xmlExport request
	    if ($this->_isXMLExport($wp->query_vars)) {
	        // process the request.
	        require_once(PHOTOQ_PATH.'panels/xml-export.php');
	        die();
	    }elseif($this->_isCronjob($wp->query_vars)){
	    	$queue = PhotoQQueue::getInstance();
			$queue->publishViaCronjob();
	    	die();
	    }
	}
	
	function _isXMLExport($queryVars){
		return $this->_isCustomRequest($queryVars, 'xmlExport');
	}
	
	function _isCustomRequest($queryVars, $name){
		return array_key_exists('photoQHandler', $queryVars) 
	            && $queryVars['photoQHandler']  == $name;
	}
	
	function _isCronjob($queryVars){
		return $this->_isCustomRequest($queryVars, 'cronjob');
	}

}