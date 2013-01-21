<?php
class PhotoQWordPressEditor implements PhotoQHookable
{
	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		// filter to show change photo form in post editing
		add_filter('edit_form_advanced', 
			array($this, 'filterShowChangePostedPhotoBox'));
		// Only show description in content field when editing
		add_filter('edit_post_content', 
			array($this, 'filterPrepareEditorContent'), 10, 2);
		// Get description back
		add_filter('wp_insert_post_data', 
			array($this, 'filterPostProcessEditedPost'), 10, 2);
		
	}
		
	
	/**
	 * Injects a form to change the photo above the wordpress wysywig editor.
	 * The technique used here was shamelessly copied from the yapb plugin. So
	 * kudos to its author johannes jarolim for figuring this one out :-)
	 * @return unknown_type
	 */
	function filterShowChangePostedPhotoBox(){
		global $post;
		if(PhotoQHelper::isPhotoPost($post->ID)){
			$db = PhotoQ_DB_DB::getInstance();
			$photo = $db->getPublishedPhoto($post->ID);
			$oc = PhotoQ_Option_OptionController::getInstance();
			
			//we are ready to show the form
			require_once(PHOTOQ_PATH.'panels/changePostedPhotoForm.php');
		}
	}

	
	/**
	 * A posted photo is being edited. Keep only its description in the editor.
	 * @param $data
	 * @param $postID
	 * @return unknown_type
	 */
	function filterPrepareEditorContent($data, $postID)
	{
		PhotoQHelper::debug('enter filterPrepareEditorContent()');
		$oc = PhotoQ_Option_OptionController::getInstance();
		if(PhotoQHelper::isPhotoPost($postID) && $oc->isManaged('content')){
			$post = get_post($postID);
			$photo = new PhotoQ_Photo_PublishedPhoto($post->ID, $post->post_title);
			$data = $photo->getDescription();	
		}
		PhotoQHelper::debug('leave filterPrepareEditorContent()');
		return $data;
	}
	
	/**
	 * Runs if photo post is saved in editor. Is executed before the database write.
	 * We here sync all the fields and update images if any were changed.
	 * @param $data
	 * @param $postarr
	 * @return unknown_type
	 */
	function filterPostProcessEditedPost($data, $postarr)
	{
		PhotoQHelper::debug('enter filterPostProcessEditedPost()');
		
		if($_POST['saveAfterEdit']){//only execute if we come from the editor
			$postID = $postarr['ID'];
			
			// verify this came from our screen and with proper authorization,
			// because save_post can be triggered at other times
			if ( !wp_verify_nonce( $_POST['photoqEditPostFormNonce'], 'photoqEditPost'.$postID )) {
				return $data;
			}

			PhotoQHelper::debug('passed check of nonce');

			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $postID ))
				return $data;
			} else {
				if ( !current_user_can( 'edit_post', $postID ))
				return $data;
			}

			PhotoQHelper::debug('passed authentication');
			
			// OK, we're authenticated we can now start to change post data
			if(PhotoQHelper::isPhotoPost($postID)){
				PhotoQHelper::debug('is photo post');
				$post = get_post($postID);
				$photo = new PhotoQ_Photo_PublishedPhoto($post->ID, $post->post_title);
			
				//upload a new photo if any
				if(array_key_exists('Filedata', $_FILES) && !empty($_FILES['Filedata']['name'])){
					$uploader = new PhotoQ_File_Uploader($photo->getOriginalDir());
	 				if($newPath = $uploader->import()){
	 					$photo->replaceImage($newPath);
	 				}
		 		}
		 		//sync the content to description and put photos back into content and excerpt
				$data = $photo->syncPostUpdateData($data);
				
				$this->_deactivateConflictingGraceThemeAction();
			}
			
		}
		PhotoQHelper::debug('leave filterPostProcessEditedPost()');
		return $data;
	}
	
	/**
	 * The Grace theme which is used often in conjunction with PhotoQ
	 * provides two non-standard custom fields for image information that
	 * are synced back to regular custom fields via an action. PhotoQ 
	 * already takes care of this and the Grace syncing overwrittes any
	 * changes done by PhotoQ. We therefore suppress this action call of
	 * Grace here.
	 */
	private function _deactivateConflictingGraceThemeAction(){
		if(get_current_theme() === 'Grace')
			remove_action('save_post', 'save_postdata'); 
	}
}