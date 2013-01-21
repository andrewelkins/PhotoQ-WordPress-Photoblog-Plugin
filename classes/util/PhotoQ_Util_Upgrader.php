<?php
class PhotoQ_Util_Upgrader implements PhotoQHookable
{	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		register_activation_hook(
			PHOTOQ_PATH . 'whoismanu-photoq.php', 
			array($this, 'activatePlugin')
		);
		
		if(PhotoQ_DB_DB::didVersionChange()){
			add_action('init', array($this, 'autoUpgrade'));
		}
		
		/*add_filter('upgrader_pre_install', 
			array(&$this, 'checkUpgradingRequirementsV2'), 1, 2);
		*/
	}
	
	/**
	 * Hook called upon activation of plugin.
	 * Installs/Upgrades the database tables.
	 *
	 */
	public function activatePlugin()
	{
		PhotoQHelper::debug('enter activatePlugin()');
		$this->_upgradeDatabase();
		PhotoQHelper::debug('leave activatePlugin()');
	}
	
	private function _upgradeDatabase(){
		$this->_updateRolesAndCapabilities();
		
		$db = PhotoQ_DB_DB::getInstance();
		$db->upgrade();
		
		//starting with 1.9.5. the queue table has a different db structure,
		//using mostly wordpress built-in tables.
		$qup = new PhotoQ_Util_UpgradePreTwoZeroQueueDB();
		$qup->upgrade();
	}
		
	/**
	 * Runs any automatic upgrading things when changing between versions.
	 */
	public function autoUpgrade(){
		$this->_upgradeFromPre18();
		$this->_upgradeDatabase();
	}
	
	/**
	 * the structure of views changed in 1.8. we don't want to 
	 * force a rebuild on our users so we deal with it here, 
	 * adjusting the old views to the new ones
	 */
	private function _upgradeFromPre18(){
		$oldOptionArray = get_option('wimpq_options');
		if(!isset($oldOptionArray['views'])){
			$views = array();
			$views['views'] = array('content' => 0, 'excerpt' => 0);
			//copy the old settings over
			$views['content'] = $oldOptionArray['contentView'];
			$views['excerpt'] = $oldOptionArray['excerptView'];
			//store the new views setting
			$oldOptionArray['views'] = $views;
			//remove the old guys
			unset($oldOptionArray['contentView']);
			unset($oldOptionArray['excerptView']);

			update_option('wimpq_options', $oldOptionArray);

			//reload to make the changes active
			$oc = PhotoQ_Option_OptionController::getInstance();
			$oc->load();
		}
	}
	
	
	/**
	 * Define what role has what capability. Called on database upgrades
	 * @return unknown_type
	 */
	private function _updateRolesAndCapabilities(){
		$capRolesArray = array(
			'access_photoq' => array('administrator','editor','author'),
			'manage_photoq_options' => array('administrator'),
			'use_primary_photoq_post_button' => array('administrator','editor','author'),
			'use_secondary_photoq_post_button' => array('administrator', 'editor'),
			'reorder_photoq' => array('administrator', 'editor', 'author')
		);

		//remove any old capabilities that might interfere with the above
		$photoQRoles = array('administrator','editor','author');
		$photoQCaps = array_keys($capRolesArray);
		foreach($photoQRoles as $roleName){
			foreach($photoQCaps as $capName){
				$currentRole = get_role($roleName);
				if(!empty($currentRole))
					$currentRole->remove_cap($capName);	
			}
		}
		//add the capabilities
		foreach($capRolesArray as $capName => $roleNames){
			foreach ($roleNames as $roleName){
				$currentRole = get_role($roleName);
				if ( !empty( $currentRole ) )
				$currentRole->add_cap($capName);
			}
		}
	}

	
}