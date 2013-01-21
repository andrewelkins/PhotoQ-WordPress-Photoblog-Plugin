<?php
class PhotoQContextualHelp implements PhotoQHookable
{
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_filter('contextual_help', 
			array($this, 'filterAddContextualHelp'), 10, 2);
	}
		
	
	/**
	 * Adds a link to the contextual help menu
	 * @param $actions
	 * @return unknown_type
	 */
	public function filterAddContextualHelp($text, $screen){
		if($screen == 'settings_page_whoismanu-photoq' || $screen = 'posts_page_whoismanu-photoq'){
			$text .= '<br/><a href="http://www.whoismanu.com/photoq-wordpress-photoblog-plugin/" target="_blank">'.__('PhotoQ Documentation','PhotoQ').'</a><br/>';
			$text .= '<a href="http://www.whoismanu.com/forum/" target="_blank">'.__('PhotoQ Support Forum','PhotoQ').'</a>';
		}
		return $text;
	}
}