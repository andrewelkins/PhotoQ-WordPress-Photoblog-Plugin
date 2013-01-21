<?php
class PhotoQLocalization implements PhotoQHookable
{
	/**
	 * To hook the appropriate callback functions
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('init', array($this, 'setupPluginLocalization'));
	}


	/**
	 * Loads the Plugin language files.
	 */
	public function setupPluginLocalization(){
		load_plugin_textdomain('PhotoQ', '', PHOTOQ_DIRNAME.'/lang');
	}
}