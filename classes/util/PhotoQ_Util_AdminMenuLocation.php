<?php
/**
 * Abstract base for helper classes that encapsulate information
 * about the menu pages photoq uses.
 *
 */
abstract class PhotoQ_Util_AdminMenuLocation
{
	const PLUGIN_MENU_SLUG = 'photoq';
	
	protected $_parentMenu;
	protected $_pageName;
	
	protected function __construct($parentMenu, $pageNameSuffix){
		$this->_parentMenu = $parentMenu;
		$this->_pageName = $this->_parentMenu . $pageNameSuffix;
	}
	
	/**
	 * Returns the name of the parent menu to which the photoq 
	 * page is attached.
	 * 
	 * @return string
	 */
	public function getParentMenu(){
		return $this->_parentMenu;
	}
	
	/**
	 * Returns the name of the page that is to be used e.g. in
	 * form actions.
	 * 
	 * @return string
	 */
	public function getPageName(){
		return $this->_pageName;
	}
}