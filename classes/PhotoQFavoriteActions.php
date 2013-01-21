<?php
class PhotoQFavoriteActions implements PhotoQHookable
{
	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_filter('favorite_actions', 
			array($this, 'filterAddFavoriteActions'));
	}
		
	
	/**
	 * Adds a link to the photo queue to the favorites action menu
	 * @param $actions
	 * @return unknown_type
	 */
	public function filterAddFavoriteActions($actions){
		$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
		$newActions = array(
			$manageMenu->getPageName() => array(__('Show PhotoQ','PhotoQ'), 'edit_posts')
		);
		return array_merge($actions,$newActions);
	}
}