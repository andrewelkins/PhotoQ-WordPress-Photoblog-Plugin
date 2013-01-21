<?php

abstract class PhotoQ_Photo_Photo
{
	
	//define names of PhotoQ custom fields
	const DESCR_FIELD_NAME = 'photoQDescr';
	const PATH_FIELD_NAME = 'photoQPath';
	const EXIF_FULL_FIELD_NAME = 'photoQExifFull';
	const EXIF_FIELD_NAME = 'photoQExif';
	const SIZES_FIELD_NAME = 'photoQImageSizes';
	
	protected $_DEFAULT_VIEWS = array('content', 'excerpt');
	
	/**
	 * Reference to OptionControllor singleton
	 * @var object PhotoQ_Option_OptionController
	 */
	protected $_oc;
	
	/**
	 * Reference to PhotoQ_DB_DB singleton
	 * @var object PhotoQ_DB_DB
	 */
	protected $_db;
	
	protected $_imgDirs;
	
	protected $_sizes = array();
	
	protected $_originalPath;
	private $_oldOriginalPath;
	
	/**
	 * The tag names of this photos. Now an array instead of comma separated list
	 * as this is often easier to handle
	 * @var array
	 */
	protected $_tags;
	
	protected $_id;
	protected $_title;
	protected $_descr;
	protected $_imgname;
	protected $_exif;
		
	protected function __construct($id, $title, $descr, $exif, $path, $imgname, $tags = '')
	{
		PhotoQHelper::debug('enter PhotoQ_Photo_Photo::__construct()');
		
		//get the other singletons
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
		$this->_db = PhotoQ_DB_DB::getInstance();
		
		$this->_imgDirs = new PhotoQ_File_ImageDirs();
		
		$this->_id = $id;
		$this->_imgname = $imgname;
		$this->_tags = $tags;
		
		$this->_title = $title;
		$this->_descr = $descr;
		$this->_exif = maybe_unserialize($exif);
		
		$this->_originalPath = $path;
		$this->_oldOriginalPath = $path;

		PhotoQHelper::debug('PhotoQ_Photo_Photo::__construct(): initImageSizes');
		
		$this->initImageSizes();
		PhotoQHelper::debug('leave PhotoQ_Photo_Photo::__construct()');
	}
	
	
	abstract protected function _determineYearMonthDir();
	
	protected function initImageSizes(){
		try{
			$originalDimension = $this->_determineOriginalDimension();
			//add all the image sizes
			foreach ($this->_oc->getImageSizeNames() as $sizeName){
				$this->_sizes[$sizeName] = PhotoQ_Photo_ImageSize::createInstance(
					$sizeName, $this->_imgname, 
					new PhotoQ_Photo_ImageSizeLocation(
						$sizeName, $this->_imgname, $this->_determineYearMonthDir()
					), 
					$originalDimension
				);
			}
			//add the original
			$this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER] = 
				PhotoQ_Photo_ImageSize::createInstance(
					PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER, $this->_imgname, 
					new PhotoQ_Photo_ImageSizeLocation(
						$this->_imgDirs->getCurrentOriginalDirName(), $this->_imgname, 
						$this->_determineYearMonthDir()
					), 
					$originalDimension
				);
		}catch(PhotoQ_Error_FileNotFoundException $e){
			$this->_raisePhotoNotFoundError();
		}
		
	}
	
	private function _determineOriginalDimension(){
		if(!file_exists($this->_originalPath)){
			//try to fix the path, if nothing helps give up with exception
			$originalLocation = new PhotoQ_Photo_ImageSizeLocation(
						$this->_imgDirs->getCurrentOriginalDirName(), $this->_imgname, 
						$this->_determineYearMonthDir()
					);
			$possiblePath = $originalLocation->getPath();
			if(!file_exists($possiblePath))
				throw new PhotoQ_Error_FileNotFoundException();
			else
				$this->_originalPath = $possiblePath;
		}
		//set original width and height
		list($width, $height) = getimagesize($this->_originalPath);
		
		return new PhotoQ_Photo_Dimension($width, $height);
	}

	private function _raisePhotoNotFoundError(){
		add_settings_error('wimpq-photoq', 'photo-not-found',
			sprintf(__('Post "%1$s": The photo "%2$s" could not be found at "%3$s".', 'PhotoQ'), $this->_title, $this->_imgname, $this->_originalPath), 'error');
	}
	
	private function _raiseSizeNotDefinedError($sizeName){
		add_settings_error('wimpq-photoq', 'size-not-defined',
			sprintf(__('The image size "%s" is not defined.', 'PhotoQ'), $sizeName), 'error');
	}
	
	/**
	 * Deletes image files associated with this photo from the server.
	 */
	function delete()
	{
		delete_transient('dirsize_cache');
		//remove from server
		$deleted = true;
		if(file_exists($this->_originalPath))
			$deleted = unlink($this->_originalPath);
		if(!$deleted)
			add_settings_error('wimpq-photoq', 'photo-delete-failed',
				sprintf(__('Could not delete photo "%s" from server. Please delete manually.', 'PhotoQ'), $this->_imgname), 'error');
			else
				add_settings_error('wimpq-photoq', 'removed-from-queue',
					__('Entry successfully removed from queue. Corresponding files deleted from server.', 'PhotoQ'), 'updated');
			return $deleted;
	}
	
	
	private function _generateImgTag($sizeName, $class)
	{		
		return '<img width="'.$this->_sizes[$sizeName]->getScaledWidth().'" height="'.$this->_sizes[$sizeName]->getScaledHeight().'" alt="'.$this->_title.'" src="'.$this->_sizes[$sizeName]->getUrl().'" class="'.$class.'" />';
	}
	
	private function _generateImgLink($sourceSizeName, $targetSizeName, $attributes, $class)
	{
		return '<a '. $attributes . ' href="'.$this->_sizes[$targetSizeName]->getUrl().'" title="'.$this->_title.'"><img width="'.$this->_sizes[$sourceSizeName]->getScaledWidth().'" height="'.$this->_sizes[$sourceSizeName]->getScaledHeight().'" alt="'.$this->_title.'" src="'.$this->_sizes[$sourceSizeName]->getUrl().'" class="'.$class.'" /></a>';
	}
	
	private function _generateFreeformView($template){
		$result = $template;
		$simpleReplacements = array(
			'title' => $this->_title,
			'descr' => $this->_descr,
			'exif' => $this->getNiceExif()
		);
		
		//handle the meta fields
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fields = $fieldTable->getAllFields();
		foreach ($fields as $field) {
			$simpleReplacements[$field->q_field_name] = $this->getField($field->q_field_name, $field->q_field_id);
		}
		
		$result = PhotoQHelper::formatShorttags($result, $simpleReplacements);
		
		$sizeReplacements = array('Url', 'Path', 'Width', 'Height');
		//foreach($simpleReplacements as $replKey => $replVal)
		//	$result = preg_replace('/\['.preg_quote($replKey).'\]/', $replVal, $result);
		
		foreach($sizeReplacements as $repl)
			$result = preg_replace_callback('/\[img'.preg_quote($repl).'\|(.+?)\]/', array($this, 'get'.$repl.'FromMatchedSize'), $result);
			
		
		return $result;
	}
	
	/**
	 * The following are dynamically called callbacks for the freeform function
	 */
	public function getUrlFromMatchedSize($matches){
		if(isset($this->_sizes[$matches[1]]))
			return $this->_sizes[$matches[1]]->getUrl();
		else{
			$this->_raiseSizeNotDefinedError($matches[1]);
			return '';
		}
	}
	
	public function getPathFromMatchedSize($matches){
		if(isset($this->_sizes[$matches[1]]))
			return $this->_sizes[$matches[1]]->getPath();
		else{
			$this->_raiseSizeNotDefinedError($matches[1]);
			return '';
		}
	}
	
	public function getWidthFromMatchedSize($matches){
		if(isset($this->_sizes[$matches[1]]))
			return $this->_sizes[$matches[1]]->getScaledWidth();
		else{
			$this->_raiseSizeNotDefinedError($matches[1]);
			return '';
		}
	}
	
	public function getHeightFromMatchedSize($matches){
		if(isset($this->_sizes[$matches[1]]))
			return $this->_sizes[$matches[1]]->getScaledHeight();
		else{
			$this->_raiseSizeNotDefinedError($matches[1]);
			return '';
		}
	}
	
	/**
	 * Generates the data stored in the_content or the_excerpt.
	 *
	 * @param string $viewName the name of the view to generate (content or excerpt).
	 * @return string	the data to be stored.
	 */
	protected function generateContent($viewName = 'content')
	{
		PhotoQHelper::debug('enter generateContent()');
		$viewType = $this->_oc->getValue($viewName . 'View-type');
		PhotoQHelper::debug('viewName: ' . $viewName. ', viewType: ' . $viewType);
		switch($viewType){

			case 'single':
				$singleSize = $this->_oc->getValue($viewName . 'View-singleSize');
				PhotoQHelper::debug('generateContent('.$viewName.') size: '. $singleSize);
				
				$data = $this->_generateImgTag($singleSize, "photoQ$viewName photoQImg");
				break;

			case 'imgLink':
				$sourceSize = $this->_oc->getValue($viewName . 'View-imgLinkSize');
				$targetSize = $this->_oc->getValue($viewName . 'View-imgLinkTargetSize');
				$data = $this->_generateImgLink($sourceSize, $targetSize,
					stripslashes(html_entity_decode($this->_oc->getValue($viewName . 'View-imgLinkAttributes'))),
					"photoQ$viewName photoQLinkImg"
				);
				break;
			case 'freeform':
				$data = $this->_generateFreeformView(stripslashes(html_entity_decode($this->_oc->getValue($viewName . 'View-freeform'))));
				break;
		}
		
		if($viewName == 'content' && $viewType != 'freeform'){
			if($this->_oc->getValue('inlineDescr'))
				//leave this on separate line or wpautop() will mess up, strange but true...
				$data .= '
				<div class="'.PhotoQ_Photo_Photo::DESCR_FIELD_NAME.'">' . $this->_descr . '</div>';
			if($this->_oc->getValue('inlineExif'))
				$data .= $this->getNiceExif();
		}
		return $data;
			
	}
	
	protected function generateSizesField()
	{
		$sizeFieldData = array();
		foreach($this->_sizes as $size){
			$imgTag = $this->_generateImgTag($size->getName(), "PhotoQImg");
			$imgUrl = $size->getUrl();
			$imgPath = $size->getPath();
			$imgWidth = $size->getScaledWidth();
			$imgHeight = $size->getScaledHeight();
			$sizeFieldData[$size->getName()] = compact('imgTag', 'imgUrl', 'imgPath', 'imgWidth', 'imgHeight');
		}	
		return $sizeFieldData;
	}
	
	
	protected function _createAttachment($postID){
		$originalPath = $this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getPath();
		$attachment = $this->_createAttachmentObject($originalPath);
     	$this->_storeAttachment($attachment, $originalPath, $postID);
	}
	
	protected function _updateAttachment($attachmentID, $postID){
		$originalPath = $this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getPath();
		$attachment = $this->_createAttachmentObject($originalPath);
		$attachment = $this->_markAttachmentForUpdate($attachment, $attachmentID);
     	$this->_storeAttachment($attachment, $originalPath, $postID);
	}
	
	private function _createAttachmentObject($originalPath){
		$filetype = wp_check_filetype($originalPath);
		return array(
     		'post_mime_type' => $filetype['type'],
     		'post_title' => $this->_title,
     		'post_content' => '',
     		'post_status' => 'inherit'
     	);
	}
	
	private function _markAttachmentForUpdate($attachment, $attachmentID){
		$attachment['ID'] = $attachmentID;
		return $attachment;
	}
	
	private function _storeAttachment($attachment, $originalPath, $postID){
		$attachmentID = wp_insert_attachment(
			$attachment, 
			str_replace($this->_oc->getImgDir(), '', $originalPath), 
			$postID
		);
  		$attachmentData = $this->_generateAttachmentMetadata($attachmentID);
 	 	wp_update_attachment_metadata($attachmentID, $attachmentData);
 	 	if($this->_oc->getValue('setFeaturedImage'))
 	 		update_post_meta( $postID, '_thumbnail_id', $attachmentID );
	}
	
	/**
	 * Generate post thumbnail attachment meta data.
	 * similar to wp_generate_attachment_metadata
	 *
	 * @param int $attachment_id Attachment Id to process.
	 * @param string $file Filepath of the Attached image.
	 * @return mixed Metadata for attachment.
	 */
	protected function _generateAttachmentMetadata($attachmentID) {
		
		$metadata = array();
		$metadata['width'] = $this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getScaledWidth();
		$metadata['height'] = $this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getScaledHeight();
			
		list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
		$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";

		$originalPath = $this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getPath();
		// Make the file path relative to the upload dir
		$metadata['file'] = _wp_relative_upload_path($originalPath);

		foreach($this->_sizes as $size){
			$metadata['sizes'][$size->getName()] = array(
				'file' => basename($size->getPath()),
				'path' => $size->getPath(),
				'url' => $size->getUrl(),
				'width' => $size->getScaledWidth(),
				'height' => $size->getScaledHeight(),
			);
		}

		//add the exif metadata
		$metadata['image_meta'] = $this->_exif;
		
		return apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachmentID );
	}
	
	
	public function hasOriginal(){
		return file_exists($this->_originalPath);
	}
	
	
	
	/**
	 * Rebuild the downsized version for a given image size.
	 *
	 * @param object PhotoQ_Photo_ImageSize $size
	 * @return boolean
	 */
	function rebuildSize(PhotoQ_Photo_ImageSize $size){
		try{
			$size->createPhoto($this->_originalPath);
		}catch(PhotoQ_Error_Exception $e){
			$this->cleanUpAfterError();
			throw $e;
		}
	}
	
	function cleanUpAfterError(){
		//move back original if it has been moved already
		$srcDest = new PhotoQ_File_SourceDestinationPair(
			$this->_sizes[PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER]->getPath(), $this->_oc->getQDir() . $this->_imgname);
		
		if ($srcDest->sourceExists() && !$srcDest->destinationExists())
			PhotoQHelper::moveFile($srcDest);
		
		//remove any resized images that have been created unless a corresponding original image exists
		if(!$srcDest->sourceExists()){
			foreach($this->_sizes as $size){
				$size->deleteResizedPhoto();
			}
		}
	}
	
	/**
	 * Rebuild downsized version of an image given the name of the downsized version.
	 *
	 * @param string $sizeName
	 * @return boolean
	 */
	function rebuildByName($sizeName){
		$this->rebuildSize($this->_sizes[$sizeName]);
	}
	
	/**
	 * Getter for the image name field
	 * @return string
	 */
	function getName(){
		return $this->_imgname;
	}
	
	/**
	 * Getter for the path field
	 * @return string
	 */
	public function getPath(){
		return $this->_originalPath;
	}
	
	public function getOldPath(){
		return $this->_oldOriginalPath;
	}
	
	/**
	 * Getter for the id field
	 * @return int
	 */
	function getId(){
		return $this->_id;
	}
	
	/**
	 * Getter for the title field
	 * @return string
	 */
	function getTitle(){
		return $this->_title;
	}
	
	/**
	 * Get the customfield with specified name.
	 * @param $name
	 * @param $id
	 * @return unknown_type
	 */
	function getField($name, $id = 0){
		return get_post_meta($this->_id, $name, true);
	}
	
	/**
	 * Getter for the descr field
	 * @return string
	 */
	function getDescription(){
		return $this->_descr;
	}
	
	function getTagString(){
		return implode(', ', $this->_tags);
	}
	
	/**
	 * Returns the formatted list of Exif data, only containing Exif tags that
	 * were selected in the PhotoQ settings.
	 * @return unknown_type
	 */
	protected function getNiceExif(){
		$displayOptions = array(
			'before' => stripslashes(html_entity_decode($this->_oc->getValue('exifBefore'))),
			'after' => stripslashes(html_entity_decode($this->_oc->getValue('exifAfter'))),
			'elementBetween' => stripslashes(html_entity_decode($this->_oc->getValue('exifElementBetween'))),
			'elementFormatting' => stripslashes(html_entity_decode($this->_oc->getValue('exifElementFormatting')))
		);
		return PhotoQExif::getFormattedExif(
			$this->_exif,
			$this->_oc->getValue('exifTags'),
			array_keys($this->_getTagsFromExifKeyValArray()),
			$this->_getExifTagsDisplayNameArray(),
			$displayOptions	
		);
	}
	
	/**
	 * Create array of tagsFromExif key value pairs for this photo
	 * @return array
	 */
	private function _getTagsFromExifKeyValArray(){
		$result = array();
		if(is_array($this->_exif) && count($this->_exif)){
			foreach($this->_exif as $key => $value){
				if($this->_oc->getValue($key.'-tag'))
					$result[$key] = $value;
			}
		}
		return $result;
	}
	
	private function _getExifTagsDisplayNameArray(){
		$result = array();
		if(is_array($this->_exif) && count($this->_exif)){
			foreach($this->_exif as $key => $value){
				$result[$key] = $this->_oc->getValue($key.'-displayName');
			}
		}
		return $result;
	}
	
	
	protected function getTagsFromExifString(){
		return implode(',', array_values($this->_getTagsFromExifKeyValArray()));
	}
	
	public function getAdminThumbImgTag(PhotoQ_Photo_Dimension $dimension = NULL){
		$dimension = is_null($dimension) ? 
			new PhotoQ_Photo_DefaultThumbDimension() : $dimension;
		
		return '<img src="'.$this->_getAdminThumbURL($dimension).'"
					 alt="'.$this->getTitle().'" />';
	}
	
	private function _getAdminThumbURL(PhotoQ_Photo_Dimension $dimension)
	{
		require_once(PHOTOQ_PATH.'lib/phpthumb-3-0/ThumbLib.inc.php');
		$thumb = PhpThumbFactory::create($this->getPath());  
		$thumb->resize($dimension->getWidth(), $dimension->getHeight());  
		return $thumb->show();   
	}
	
	
}
