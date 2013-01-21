<?php
class PhotoQQueuedPostType implements PhotoQHookable
{

	const POST_TYPE_NAME = 'photoq_queued_photo';
	
	/**
	 * To hook the appropriate callback functions (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_action('init', array($this, 'createPostType') );
	}
	
	public function createPostType(){
		register_post_type(self::POST_TYPE_NAME,
			array(
				'labels' => array(
					'name' => __('Queued Photos', 'PhotoQ'),
					'singular_name' => __('Queued Photo', 'PhotoQ')
				),
				'taxonomies' => array(
					'category', 'post_tag'
				),
				'public' => false,
			)
		);
		
		$this->_registerCustomTaxonomies();
		
	}
	
	private function _registerCustomTaxonomies(){
		foreach(PhotoQ_Util_Taxonomies::getCustomTaxonomies() as $taxonomy)
			register_taxonomy_for_object_type($taxonomy, self::POST_TYPE_NAME);
	}
}