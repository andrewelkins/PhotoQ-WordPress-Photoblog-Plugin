<?php
abstract class PhotoQ_DB_Table
{
	
	/**
	 * The wordpress database object to interface with wordpress database
	 * @var Object
	 * @access protected
	 */
	protected $_wpdb;
	
	/**
	 * Name of the table
	 * @var unknown_type
	 */
	protected $_name;
	
	/**
	 * The Create statement defining the table.
	 * @var string
	 */
	protected $_definition;

	
	
	protected function __construct($name){
		global $wpdb;

		// set wordpress database
		$this->_wpdb = $wpdb;
		$this->_name = $name;
		
		$this->_setTableDefinition();
	}
	
	public function getFullName(){
		return $this->_wpdb->prefix . $this->_name;
	}
	
	private function _setTableDefinition(){
		$this->_definition = 'CREATE TABLE ' . $this->getFullName() . ' ';
		$this->_definition .= $this->_getFieldDefinition() . ' ';
		$this->_definition .= $this->_getCollation() . ';';
	}
	
	/**
	 * determine charset/collation stuff same way wordpress does
	 * @return string
	 */
	private function _getCollation(){
		$charsetCollate = '';
		if ( $this->_wpdb->supports_collation() ) {
			if ( ! empty($this->_wpdb->charset) )
				$charsetCollate = 'DEFAULT CHARACTER SET ' .$this->_wpdb->charset;
			if ( ! empty($this->_wpdb->collate) )
				$charsetCollate .= ' COLLATE ' . $this->_wpdb->collate;
		}
		return $charsetCollate;
	}
	
	abstract protected function _getFieldDefinition();
	
	/**
	 * Upgrades the Wordpress Database Table. 
	 * Done according to the instructions given here: 
	 * 
	 * http://codex.wordpress.org/Creating_Tables_with_Plugins
	 *
	 * @param string $table	The name of the table to update.
	 * @param string $sql	The query to run.
	 * @access private
	 */
	public function upgradeDB() {
		$table = $this->getFullName();
		if($this->_wpdb->get_var("show tables like '$table'") != $table 
					|| PhotoQ_DB_DB::didVersionChange() ) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($this->_definition);
		}	
	}
}