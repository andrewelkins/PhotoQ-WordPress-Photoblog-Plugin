<?php
/**
 * This class encapsulates information about the "manage photoq" admin
 * menu page. By default, the page is located under the posts menu, if
 * a custom post type is selected for photoq posts, it is however found
 * under the corresponding custom post type menu.
 */
class PhotoQ_Util_ManageAdminMenuLocation 
		extends PhotoQ_Util_AdminMenuLocation
{	
	public function __construct(){
		$postType = PhotoQ_Option_OptionController::getInstance()->getValue('qPostType');
		if($postType === 'post'){
			$this->_setDefaultMenuLocation();
		}else{
			$this->_setCustomPostTypeMenuLocation($postType);
		}
	}
	
	private function _setDefaultMenuLocation(){
		parent::__construct(
			'edit.php',
			'?page=' . parent::PLUGIN_MENU_SLUG
		);
	}
	
	private function _setCustomPostTypeMenuLocation($postType){
		parent::__construct(
			'edit.php?post_type=' . $postType,
			'&amp;page=' . parent::PLUGIN_MENU_SLUG
		);
	}
}