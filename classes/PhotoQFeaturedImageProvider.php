<?php
/**
 * 
 * This filter is right now needed for WP to serve the downsized images instead of the original
 * image in calls to the_post_thumbnail. The reason is that the standard wordpress function in
 * wp-includes/media.php ignores the 'url' field that is set in attachments (and in particular 
 * those created by photoq). I also submitted a ticket on the trac, once it gets into core, it 
 * should be possible to remove this class and all references to it.
 * @author manu
 *
 */
class PhotoQFeaturedImageProvider
{
	
	private $_fileUrl = '';
	private $_meta = array();
		
	
	public function __construct(){
		add_filter('image_downsize', array($this, 'filterImageDownsize'), 10, 3);
	}
	
	/**
	 * 
	 * Essentially a refactored version of the built-in image_downsize() function. However, 
	 * this implementation considers the 'url' field that is set by image_get_intermediate_size
	 * and doesn't create the path and urls solely based on the filename.
	 * @param unknown_type $data
	 * @param unknown_type $attachmentID
	 * @param unknown_type $size
	 */
	public function filterImageDownsize($data, $attachmentID, $size){
		if($this->_otherPluginAlreadyFiltered($data)) return $data;
		try{
			$this->_init($attachmentID);
			$result = $this->_imageGetIntermediateSize($size);
			if ($result->hasZeroDimension() && isset($this->_meta['width'], $this->_meta['height'])){
				$result->setDimension($this->_meta['width'], $this->_meta['height']);
			}
			$result->constrainSizeForEditor($size);
			return $result->toArray();	
		}catch(PhotoQCannotHandleDownsizingException $e){
			return false;
		}
	}
	
	private function _otherPluginAlreadyFiltered($data){
		return $data !== false;
	}
	
	private function _init($attachmentID){
		if(	!is_array($this->_meta = wp_get_attachment_metadata($attachmentID)) ||
			!$this->_fileUrl = wp_get_attachment_url($attachmentID)
		)
			throw new PhotoQCannotHandleDownsizingException();
	}

	private function _imageGetIntermediateSize($size) {
		try {
			if (!$this->_isRegistered($size)) {
				return $this->_buildIntermediateImage($this->_findBestMatchingIntermediate($size));
			}
			return $this->_buildIntermediateImage($size);
		}catch(PhotoQNoMatchingIntermediateException $e){
			return $this->_buildDefaultIntermediateImage($size);
		}
	}
	
	private function _isRegistered($size){
		return !is_array($size);
	}
	
	// include the full filesystem path of the intermediate file
	private function _buildIntermediateImage($size){
		if(empty($this->_meta['sizes'][$size])) throw new PhotoQNoMatchingIntermediateException();
		$data = $this->_meta['sizes'][$size];
		$result = new PhotoQIntermediateImage($this->_getUrl($data));
		$result->setDimension($data['width'], $data['height']);
		$result->enableIntermediate();
		return $result;
	}
	
	private function _getUrl($data){
		if ( empty($data['url']) && !empty($data['file']) ) {
			$data['url'] = path_join( dirname($this->_fileUrl), $data['file'] );
		}
		if(!$data['url']) throw new PhotoQCannotHandleDownsizingException();
		return $data['url'];
	}
	
	// get the best one for a specified set of dimensions
	private function _findBestMatchingIntermediate($dimension){
		if($this->_impossibleToFindMatch($dimension))
			throw new PhotoQNoMatchingIntermediateException();
			
		foreach ( $this->_meta['sizes'] as $_size => $data ) {
			// already cropped to width or height; so use this size
			if ( ( $data['width'] == $dimension[0] && $data['height'] <= $dimension[1] ) || ( $data['height'] == $dimension[1] && $data['width'] <= $dimension[0] ) ) {
				return $_size;
			}
			// add to lookup table: area => size
			$areas[$data['width'] * $data['height']] = $_size;
		}
		if ( !$dimension || !empty($areas) ) {
			// find for the smallest image not smaller than the desired size
			ksort($areas);
			foreach ( $areas as $_size ) {
				$data = $this->_meta['sizes'][$_size];
				if ( $data['width'] >= $dimension[0] || $data['height'] >= $dimension[1] ) {
					// Skip images with unexpectedly divergent aspect ratios (crops)
					// First, we calculate what size the original image would be if constrained to a box the size of the current image in the loop
					$maybe_cropped = image_resize_dimensions($this->_meta['width'], $this->_meta['height'], $data['width'], $data['height'], false );
					// If the size doesn't match within one pixel, then it is of a different aspect ratio, so we skip it, unless it's the thumbnail size
					if ( 'thumbnail' != $_size && ( !$maybe_cropped || ( $maybe_cropped[4] != $data['width'] && $maybe_cropped[4] + 1 != $data['width'] ) || ( $maybe_cropped[5] != $data['height'] && $maybe_cropped[5] + 1 != $data['height'] ) ) )
					continue;
					// If we're still here, then we're going to use this size
					return $_size;
				}
			}
		}
		
		throw new PhotoQNoMatchingIntermediateException();
	}
	
	private function _impossibleToFindMatch($size){
		 return empty($size) || empty($this->_meta['sizes']); 
	}	

	private function _buildDefaultIntermediateImage($size){
		if ($size == 'thumbnail') throw new PhotoQCannotHandleDownsizingException();
		return new PhotoQIntermediateImage($this->_fileUrl);
	}
	
	
}


class PhotoQCannotHandleDownsizingException extends Exception {}
class PhotoQNoMatchingIntermediateException extends Exception {}

/**
 * 
 * Helper class corresponding to the result of the filter.
 * @author manu
 *
 */
class PhotoQIntermediateImage
{
	private $_url, $_w, $_h, $_isIntermediate;
	
	public function __construct($url){
		$this->_url = $url;
		$this->_w = $this->_h = 0;
		$this->_isIntermediate = false;
	}
	
	public function toArray(){
		return array($this->_url, $this->_w, $this->_h, $this->_isIntermediate);
	}
	
	public function setDimension($w, $h){
		$this->_w = $w;
		$this->_h = $h;
	}
	
	public function enableIntermediate(){
		$this->_isIntermediate = true;
	}
	
	public function constrainSizeForEditor($size){
		// we have the actual image size, but might need to further constrain it if content_width is narrower
		list($this->_w, $this->_h) = image_constrain_size_for_editor($this->_w, $this->_h, $size);	
	}
	
	public function hasZeroDimension(){
		return !$width && !$height;
	}
}