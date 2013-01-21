<?php
class PhotoQ_Util_GarbageCollector implements PhotoQHookable
{	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		$oc = PhotoQ_Option_OptionController::getInstance();
		if($oc->getValue('deleteImgs'))
			add_action('delete_post', array($this, 'actionCleanUp'));
		
		add_action('delete_attachment', array($this, 'actionDeleteAttachmentImageSizes'));
	}
	
	/** 
	 * sink function executed whenever a post is deleted. 
	 * Takes post id as argument. Deletes the corresponding image 
	 * and thumb files from server if post is deleted.
	 */
	public function actionCleanUp($id){
		if(PhotoQHelper::isPhotoPost($id)){
			$post = get_post($id);
			$photo = new PhotoQ_Photo_PublishedPhoto(
				$post->ID, $post->title
			);
			$photo->delete();
		}
	}
	
	/**
	 * Deletes image files that correspond to the image sizes defined in PhotoQ.
	 * This function does pretty much what part of wp-includes/post.php/wp_delete_attachment()
	 * does. We do this here separately because in post.php only files from image sizes
	 * that are registered with WordPress get deleted. We don't want to register the PhotoQ image
	 * sizes, otherwise we would have meaningless images created for ALL attachments.
	 * @param integer $attachmentID
	 */
	public function actionDeleteAttachmentImageSizes($attachmentID){
		$oc = PhotoQ_Option_OptionController::getInstance();
		// remove image sizes if there are any
		foreach ( $oc->getImageSizeNames() as $size ) {
			if ( $intermediate = image_get_intermediate_size($attachmentID, $size) ) {
				$intermediate_file = apply_filters('wp_delete_file', $intermediate['path']);
				@ unlink( path_join($oc->getImgDir(), $intermediate_file) );
			}
		}
	}

	
}