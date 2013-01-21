<?php
interface PhotoQHookable
{
	/**
	 * To hook the appropriate callback functions (action and filter hooks) 
	 * into the WordPress Plugin API.
	 */
	public function hookIntoWordPress();
}