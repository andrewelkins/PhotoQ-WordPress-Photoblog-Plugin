<?php
/**
 * Encapsulates information about the location of the photoq settings page.
 */
class PhotoQ_Util_SettingsAdminMenuLocation extends PhotoQ_Util_AdminMenuLocation
{
	public function __construct(){
		parent::__construct(
			'options-general.php',
			'?page=' . parent::PLUGIN_MENU_SLUG
		);
	}
	
}