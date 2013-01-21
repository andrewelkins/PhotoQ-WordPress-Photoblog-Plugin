<?php

class PhotoQ_Photo_PublishedPhoto extends PhotoQ_Photo_Photo
{

	function __construct($postID, $title, $path = '')
	{
		if(empty($path)) $path = get_post_meta($postID, PhotoQ_Photo_Photo::PATH_FIELD_NAME, true);
		$descr = get_post_meta($postID, PhotoQ_Photo_Photo::DESCR_FIELD_NAME, true);
		$exif = get_post_meta($postID, PhotoQ_Photo_Photo::EXIF_FULL_FIELD_NAME, true);
		
		//read imgname from path
		$imgname = basename($path);
		parent::__construct($postID, $title, $descr, $exif, $path, $imgname, PhotoQHelper::getArrayOfTagNames($postID));
	}
	
	protected function _determineYearMonthDir(){
		return array_pop(explode('/', dirname($this->_originalPath))) . "/";
	}
	
	/**
	 * For published photos we also delete the thumbs.
	 *
	 */
	function delete()
	{
		foreach($this->_sizes as $size){
			$size->deleteResizedPhoto();
		}
		$this->_deleteAttachment();

		parent::delete();
	}

	private function _deleteAttachment(){
		if($attID = $this->_findAttachmentIDToUpdate())
			wp_delete_attachment($attID, true);
	}

		
	/**
	 * Rebuild the entire post and all the thumbs of a published photo.
	 * @param $changedSizes
	 * @param $updateExif
	 * @param $changedViews
	 * @param $updateOriginalFolder
	 * @param $oldFolder
	 * @param $newFolder
	 * @return unknown_type
	 */
	function rebuild($changedSizes, $updateExif = true, $changedViews = array(),
		$updateOriginalFolder = false, $oldFolder = '', $newFolder = '', 
		$addedTags = array(), $deletedTags = array()){
		PhotoQHelper::debug('updatePath: ' . $oldFolder.', ' . $newFolder);
		if($updateOriginalFolder)
			$this->_updatePath($oldFolder,$newFolder);

		if($this->hasOriginal()){ //make sure it is not null due to an error when creating the photo
			
			foreach ($changedSizes as $changedSize){
				try{
					$this->rebuildByName($changedSize);
				}catch(PhotoQ_Error_Exception $e){
					$e->pushOntoErrorStack();
				}
			}
			
			if(count($changedSizes) || $updateOriginalFolder)
				$this->_updateSizesField();
				
			//update media library attachment
			if(count($changedSizes) || $updateOriginalFolder || $updateExif){
				if($attID = $this->_findAttachmentIDToUpdate())
					$this->_updateAttachment($attID, $this->_id);	
			}

			//update the tags
			if(!empty($addedTags) || !empty($deletedTags))	
				$this->_updateTags($addedTags,$deletedTags);
				
			//update the formatted exif field
			if($updateExif){
				$this->_updateExif();
			}
		
			//also update the post content like we do for view changes
			if( $changedViews )
				$this->_updateViews($changedViews);
		}
	}
	
	private function _findAttachmentIDToUpdate(){
		$attachments = get_children(array(
			'post_parent' => $this->_id, 
			'post_status' => 'inherit', 
			'post_type' => 'attachment', 
			'post_mime_type' => 'image'
		));
		foreach($attachments as $att){
			$file = get_post_meta($att->ID, '_wp_attached_file', true);
			if(strpos($this->getOldPath(), $file) !== false)
				return $att->ID;
		}
		return 0;
	}

	private function _updateViews($changedViews, $customOnly = false){
		$updatePost = false;
		foreach($changedViews as $currentView){
			if(!in_array($currentView,$this->_DEFAULT_VIEWS)){
				$this->_updateCustomView($currentView);
			}else
				$updatePost = true;
		}
		if($updatePost && !$customOnly)//content or excerpt view changed -> update post
			$this->_updatePost();
	}
	
	/**
	 * Updates the field corresponding to custom view with given name.
	 *
	 */
	private function _updateCustomView($name)
	{
		update_post_meta($this->_id, $name, $this->generateContent($name));
	}

	/**
	 * Updates the content of an already published photo post.
	 *
	 * @return integer the ID of the post
	 */
	private function _updatePost()
	{
		PhotoQHelper::debug('enter _updatePost()');
		$ID = $this->_id;
		$varNames = array();
		if($this->_oc->isManaged('content')){
			$post_content = $this->generateContent();
			array_push($varNames,'post_content');
		}
		if($this->_oc->isManaged('excerpt')){
			$post_excerpt = $this->generateContent('excerpt');
			array_push($varNames,'post_excerpt');
		}
		$postData = compact('ID', $varNames);
		$postData = add_magic_quotes($postData);
		$res = wp_update_post($postData);
		//kill revisions
		$this->_db->removeRevisions($ID);	
		return $res;
	}
	
	/**
	 * Update the path replacing $old by $new in path meta field.
	 *
	 * @param string $old
	 * @param string $new
	 */
	private function _updatePath($old, $new)
	{
		PhotoQHelper::debug('old: ' . $old . ' new: ' . $new);
		$this->_originalPath = str_replace($old, $new, $this->_originalPath);
		//convert backslashes (windows) to slashes
		$this->_originalPath = str_replace('\\', '/', $this->_originalPath);
		
		$this->_imgname = basename($this->_originalPath);
		
		update_post_meta($this->_id, PhotoQ_Photo_Photo::PATH_FIELD_NAME, $this->_originalPath);
		
		//finally we need to re-init the image sizes as the path changed
		$this->initImageSizes();
	}
	
	/**
	 * Updates the tagsFromExif of the current post.
	 *
	 */
	private function _updateTags($addedTagNames = array(), $deletedTagNames = array())
	{
		//create value array from name arrays first
		$addedTags = $this->_getExifValueArray($addedTagNames);
		$deletedTags = $this->_getExifValueArray($deletedTagNames);
		PhotoQHelper::debug('added: '. print_r($addedTagNames,true));
		PhotoQHelper::debug('deleted: '. print_r($deletedTagNames,true));
		//make sure we don't have double entries
		$this->_tags = array_unique($this->_tags);
		
		//remove tags that were deleted
		$this->_tags = array_diff($this->_tags, $deletedTags);
		
		//add tags that were added
		$this->_tags = array_unique(array_merge($this->_tags, $addedTags));
		
		//update the tags in the database
		wp_set_post_tags( $this->_id, add_magic_quotes($this->_tags) );
		
		PhotoQHelper::debug($this->getName().' tags: ' . implode(',',$this->_tags) );
	}
	
	/**
	 * Updates the formatted exif of an already published photo post.
	 *
	 */
	private function _updateExif()
	{
		update_post_meta($this->_id, PhotoQ_Photo_Photo::EXIF_FIELD_NAME, $this->getNiceExif());
	}
	
	/**
	 * Helper function for the updateExif function. Takes an array of exif tags (keys)
	 * and returns an array with the corresponding Exif values for the current post.
	 * @param $keys
	 * @return array
	 */
	private function _getExifValueArray($keys){
		$result = array();
		foreach($keys as $key){
			if(is_array($this->_exif) && array_key_exists($key,$this->_exif))
				$result[] = $this->_exif[$key];
		}
		return $result;
	}
	
	/**
	 * Updates the field containing info on image sizes.
	 *
	 */
	private function _updateSizesField()
	{
		update_post_meta($this->_id, PhotoQ_Photo_Photo::SIZES_FIELD_NAME, $this->generateSizesField());
	}
	
	public function getOriginalDir()
	{
		return str_replace($this->_imgname, '', $this->_originalPath);
	}
	
	public function replaceImage($pathToNewImage){
		//new photo was uploaded, now replace the old one
		$this->delete();
		$this->_updatePath($this->getOldPath(), $pathToNewImage);
		$this->initImageSizes();

		//get new exif data
		$this->_exif = PhotoQExif::readExif($pathToNewImage);
		//update full exif in database
		update_post_meta($this->_id, PhotoQ_Photo_Photo::EXIF_FULL_FIELD_NAME, $this->_exif);
		
		//rebuild the whole thing
		$this->rebuild($this->_oc->getImageSizeNames());
	}
	
	/**
	 * Called whenever a photo post is edited and saved in the wordpress editor but before the
	 * database write. If the content changed, we sync the change to the description custom field 
	 * and put images and stuff back into the_content and the_excerpt.
	 * @param $data	array the data to be written to the database
	 * @return array the updated data
	 */	
	public function syncPostUpdateData($data){
		PhotoQHelper::debug('enter syncPostUpdateData()');
		//get the description, add formatting, e.g. replace line breaks with <p>
		$this->_descr = apply_filters('the_content', $data['post_content']);
		//sync it with the field
		update_post_meta($this->_id, PhotoQ_Photo_Photo::DESCR_FIELD_NAME, $this->_descr);
		//put photos back into excerpt and content
		if($this->_oc->isManaged('content'))
			$data['post_content'] = $this->generateContent();
		if($this->_oc->isManaged('excerpt'))
			$data['post_excerpt'] = $this->generateContent('excerpt');
		//update all custom views
		$this->_updateViews($this->_oc->getViewNames(), true);
		PhotoQHelper::debug('leave syncPostUpdateData()');
		return $data;
	}
	
	

	
	/**
	 * Our own little parser as there doesn't seem to be a reasonable one that works
	 * with both PHP4 and PHP5. A bit cumbersome and certainly not nice but it seems
	 * to work.
	 *
	 * @param string $content
	 * @return string
	 */
	public function getInlineDescription($content, $className = 'photoQDescr'){
		$descr = '';
		$photoQDescrTagsInnerHTML = array(); 
		$pTags = PhotoQHelper::getHTMLTags('div', $content);
		PhotoQHelper::debug('pTags: ' . print_r($pTags,true));
		
		foreach($pTags as $pTag){
			$matches = array();
			$found = preg_match('#^(<div.*?class="'.$className.'".*?>)#',$pTag,$matches);
			if($found){
				//remove the p start and end tag, the rest is the description.
				array_push($photoQDescrTagsInnerHTML, str_replace($matches[1],'',substr($pTag,0,strlen($pTag)-6)));
			}
		}
		
		PhotoQHelper::debug('photoQDescrTagsInnerHTML: ' . print_r($photoQDescrTagsInnerHTML,true));
		
		//if we have more than one p.photoQDescr tag, it means that there were several
		//lines created in the editor -> wrap each one with a p tag.
		$numDescrTags = count($photoQDescrTagsInnerHTML);
		if($numDescrTags == 1)
			$descr = $photoQDescrTagsInnerHTML[0];
		else
			for ($i = 0; $i < $numDescrTags; $i++){
				if($photoQDescrTagsInnerHTML[$i] !== '')
					$descr .= "<p>$photoQDescrTagsInnerHTML[$i]</p>";
			}
		
		PhotoQHelper::debug('descr:' . $descr);
		return $descr;
	}
	
}



