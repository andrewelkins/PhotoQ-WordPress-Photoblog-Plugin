<?php

/**
 * Option controller subclass responsible for handling options of the PhotoQ plugin.
 * @author: M. Flury
 * @package: PhotoQ
 *
 */
class PhotoQ_Option_OptionController extends RO_OptionController implements PhotoQSingleton
{
	
	private static $_singletonInstance;
	
	/**
	 * Reference to PhotoQ_DB_DB singleton
	 * @var object PhotoQ_DB_DB
	 */
	private $_db;
	
	
	private $_presetCategories;
	
	private $_imageDirs;
	
	public function __construct()
	{
		PhotoQHelper::debug('enter PhotoQ_Option_OptionController::__construct()');
		
		parent::__construct("wimpq_options", new PhotoQ_Option_RenderOptionVisitor());
		
		//get the db object
		$this->_db = PhotoQ_DB_DB::getInstance();
			
		$this->_presetCategories = array(
					'photoblog' 	=> __('Photoblog Themes', 'PhotoQ'),
					'textblog'		=> __('Textblog Themes','PhotoQ'),
					'mixed'			=> __('Mixed (Text/Photoblog)','PhotoQ'),
					'commercial'	=> __('Commercial Themes', 'PhotoQ')
		);
		
		$this->_imageDirs = new PhotoQ_File_ImageDirs();
			
		//establish default options
		$this->_defineAndRegisterOptions();
		
		//localize strings in js scripts etc. of option controller
		$this->localizeStrings(array(
				"switchLinkLabel" => __('Switch Sides', 'PhotoQ')
			)
		);
		
		PhotoQHelper::debug('leave PhotoQ_Option_OptionController::__construct()');
		
	}
	
	public static function getInstance()
	{
		if (!isset(self::$_singletonInstance)) {
			self::$_singletonInstance = new self();
		}
		return self::$_singletonInstance;
	}
	
	
	/**
	 * Defines all the plugin options and registers them with the OptionController.
	 *
	 * @access private
	 */
	private function _defineAndRegisterOptions()
	{
		
		//define general tests not associated to options but that should be passed
		$this->addTest(new RO_Validation_SafeModeOff(array($this,'queueValidationError')));
		$this->addTest(new RO_Validation_GDAvailable(array($this,'queueValidationError')));
		$this->addTest(new RO_Validation_WordPressVersion(array($this,'queueValidationError'),'3.0',''));

		//we try to define options that are used most frequently first so that they are found 
		//the quickest when sequentially searching through options. really need to look into 
		//alternative data structures as well to speed up this process.
		
		//path options
		if(!is_multisite()){ //no imgdir and ftp setting in WPMU
			/*$imgdir = new RO_Option_StrictValidationTextField(
				'imgdir',
				'wp-content',
				'',
				'',
				'<br />'. sprintf(__('Default is %s','PhotoQ'), '<code>wp-content</code>')
			);
			$imgdir->addTest(new RO_Validation_DirExists(array($this,'queueValidationError'),'',
			__('Image Directory not found','PhotoQ'). ': '));
			$imgdir->addTest(new RO_Validation_FileWritable(array($this,'queueValidationError'),'',
			__('Image Directory not writable','PhotoQ'). ': '));
			$this->registerOption($imgdir);*/
			
			$enableFTP = new RO_Option_CheckBox(
				'enableFtpUploads',
				'0',
				__('Allow importing of photos from the following directory on the server','PhotoQ'). ': '
			);
			$enableFTP->addChild(
				new RO_Option_TextField(
					'ftpDir',
					'',
					'',
					'',
					'<br />'. sprintf(__('Full path (e.g., %s)','PhotoQ'),'<code>'.ABSPATH.'wp-content/ftp</code>')
				)
			);
			$this->registerOption($enableFTP);
		
		}//end if(!is_multisite())
		
		$imagemagickPath = new RO_Option_TextFieldSiteOption(
				'imagemagickPath',
				'',
				sprintf(_x('Absolute path to the ImageMagick convert executable. (e.g. %1$s ). Leave empty if %2$s is in the path.| example programname','PhotoQ'),'<code>/usr/bin/convert</code>','"convert"')
		);
		
		$this->registerOption($imagemagickPath);
		
		
		//image sizes
		$imageSizes = new PhotoQ_Option_ImageSizeContainer(
			'imageSizes', 'PhotoQ_Option_ImageSizeOption', 
			array($this,'addImageSizeCallback'), array($this,'delImageSizeCallback'), 
			array(PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER), array(),
			'',
			'<table width="100%" cellspacing="2" cellpadding="5" class="form-table noborder"><tr valign="top">
					<th scope="row">
						<label for="newExpComp-imageSizes">'.__('Name of new image size', 'PhotoQ').':</label>
					</th>
					<td>',
			'</td></tr></table>'
		);
		
		$imageSizes->addChild(new PhotoQ_Option_ImageSizeOption(PhotoQ_File_ImageDirs::THUMB_IDENTIFIER, '', '80', '60'), 0);
		$imageSizes->addChild(new PhotoQ_Option_ImageSizeOption(PhotoQ_File_ImageDirs::MAIN_IDENTIFIER), 0);
		
		$this->registerOption($imageSizes);
		
		
		$originalFolder = new RO_Option_Composite('originalFolder');
		$originalFolder->addChild(
			new RO_Option_CheckBox(
				'hideOriginals',
				'0',
				__('Hide folder containing original photos. If checked, PhotoQ will attribute a random name to the folder.','PhotoQ'),
				'',
				''
			)
		);
		$this->registerOption($originalFolder);
		
		
		//next we define the views
		
		$contentView = new PhotoQ_Option_ViewOption('content', true);
		$contentView->addChild(
			new RO_Option_CheckBox(
				'inlineDescr',
				'1',
				__('Include photo description in post content (does not apply to freeform mode).','PhotoQ'),
				'<tr><th>'. __('Photo Description','PhotoQ'). ':</th><td>',
				'</td></tr>'
			)
		);
		$contentView->addChild(
			new RO_Option_CheckBox(
				'inlineExif',
				'0',
				__('Include Exif data in post content (does not apply to freeform mode).','PhotoQ'),
				'<tr><th>'. __('Exif Meta Data','PhotoQ'). ':</th><td>',
				'</td></tr>'
			)
		);
	
		
		$excerptView = new PhotoQ_Option_ViewOption('excerpt', true);
		
		
		$photoQViews = new RO_Option_ExpandableComposite(
			'views', 'PhotoQ_Option_ViewOption',
			array($this,'addViewCallback'), array($this,'delViewCallback'), 
			array(), array()
		);
		
		$photoQViews->addChild($contentView, 0);
		$photoQViews->addChild($excerptView, 0);
		$this->registerOption($photoQViews);
		
		
		//furhter options
		
		$cronOptions = new RO_Option_Composite('cronJobs');
		$cronOptions->addChild(
			new RO_Option_TextField(
				'cronFreq',
				'23',
				__('Cronjob runs every','PhotoQ'). ' ',
				'',
				__('hours','PhotoQ'),
				'3',
				'5'
			)
		);
		$cronOptions->addChild(
			new RO_Option_CheckBox(
				'cronPostMulti',
				'0',
				__('Use settings of second post button for automatic posting.','PhotoQ'),
				'<p>', '</p>'
			)
		);
		if(!is_multisite()){ //no ftp setting in WPMU
			$cronOptions->addChild(
			new RO_Option_CheckBox(
				'cronFtpToQueue',
				'0',
				__('When cronjob runs, automatically add FTP uploads to queue.','PhotoQ'),
				'<p>', '</p>'
				)
			);
		}
		$this->registerOption($cronOptions);

		$adminThumbs = new RO_Option_Composite('showThumbs', '1','','<table>','</table>');
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'showThumbs-Width',
				'120',
				'',
				'<tr><td>'._x('Thumbs shown in list of published photos are maximum | ends with: px wide','PhotoQ'). '</td><td>',
				_x('px wide| starts with: thumbs ... are','PhotoQ'). ', ',
				'3',
				'3'
			)
		);
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'showThumbs-Height',
				'60',
				'',
				' ',
				__('px high','PhotoQ'). '. <br/></td></tr>',
				'3',
				'3'
			)
		);
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'photoQAdminThumbs-Width',
				'200',
				'',
				'<tr><td>'.__('Thumbs shown in PhotoQ edit dialogs are maximum','PhotoQ'). '</td><td>',
				__('px wide','PhotoQ'). ', ',
				'3',
				'3'
			)
		);
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'photoQAdminThumbs-Height',
				'90',
				'',
				' ',
				__('px high','PhotoQ'). '. <br/></td></tr>',
				'3',
				'3'
			)
		);
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'editPostThumbs-Width',
				'300',
				'',
				'<tr><td>'.__('Thumbs shown in WordPress post editing dialog are maximum','PhotoQ'). '</td><td>',
				__('px wide','PhotoQ'). ', ',
				'3',
				'3'
			)
		);
		$adminThumbs->addChild(
			new RO_Option_TextField(
				'editPostThumbs-Height',
				'400',
				'',
				' ',
				__('px high','PhotoQ'). '.</td></tr>',
				'3',
				'3'
			)
		);
		$this->registerOption($adminThumbs);
		
		$this->registerOption(
			new RO_Option_CheckBox(
				'descrFromExif',
				'0',
				__('Get default description automatically from EXIF &ldquo;ImageDescription&rdquo; field.','PhotoQ') 
			)
		);
		
		$this->registerOption(
			new RO_Option_CheckBox(
				'dateFromExif',
				'0',
				__('Use EXIF date as post date.','PhotoQ') 
			)
		);
		
		$this->registerOption(
			new RO_Option_CheckBox(
				'setFeaturedImage',
				'0',
				__('Automatically set featured image.','PhotoQ') 
			)
		);
		
		$autoTitles = new RO_Option_Composite('autoTitles');
		$autoTitles->addChild(
			new RO_Option_CheckBox(
				'autoTitleFromExif',
				'0',
				__('Get auto title from EXIF &ldquo;ImageDescription&rdquo; field instead of filename, unless field is empty.','PhotoQ') . '<br/>'
			)
		);
		$autoTitles->addChild(
			new RO_Option_TextField(
				'autoTitleRegex',
				'', __('Custom Filter','PhotoQ'). ':', 
				'', 
				'<br/>
				<span class="setting-description">'. 
				sprintf(__('An auto title is a title that is generated automatically from the filename. By default PhotoQ creates auto titles by removing the suffix from the filename, replacing hyphens and underscores with spaces and by capitalizing the first letter of every word. You can specify an additional custom filter to remove more from the filename above. Perl regular expressions are allowed, parts of filenames that match the regex are removed (regex special chars %s need to be escaped with a backslash). Note that the custom filter is applied first, before any of the default replacements.','PhotoQ'),'<code>. \ + * ? [ ^ ] $ ( ) { } = ! < > | :</code>') 
				. '<br/>'.
				__('Examples: <code>IMG</code> to remove the string "IMG" from anywhere within the filename, <code>^IMG</code> to remove "IMG" from beginning of filename.','PhotoQ').'</span>'
			)
		);
		$autoTitles->addChild(
			new RO_Option_TextField(
				'autoTitleNoCapsShortWords',
				'2', 
				'<br/><br/>' . __('Do not capitalize words with','PhotoQ'). ' ', 
				'', 
				' ' . __('characters or less,', 'PhotoQ'),
				2,2
			)
		);
		$autoTitles->addChild(
			new RO_Option_TextField(
				'autoTitleCaps',
				'I', 
				' ' . __('except for the following words','PhotoQ'). ':<br/>', 
				'', 
				'
				<span class="setting-description">'. 
				__('(Separate words with commas)', 'PhotoQ') 
				. '</span><br/><br/>',
				100,200
			)
		);
		$autoTitles->addChild(
			new RO_Option_TextArea(
				'autoTitleNoCaps',
				_x('for, and, nor, but, yet, both, either, neither, the, for, with, from, because, after, when, although, while|english words that are not capitalized', 'PhotoQ'), 
				' ' . __('Do not capitalize any of the following words (Separate words with commas)','PhotoQ'). ':<br/>', 
				'', 
				'',
				2,100
			)
		);
		$this->registerOption($autoTitles);
		
		
		$this->registerOption(
			new RO_Option_TextField(
				'postMulti',
				'999',
				__('Second post button posts ','PhotoQ'),
				'',
				__(' photos at once.','PhotoQ'),
				'3',
				'3'
			)
		);
		
		$this->registerOption(
			new RO_Option_CheckBox(
				'foldCats',
				'0',
				__('Fold away taxonomy lists per default.','PhotoQ')
			)
		);
		
		$this->registerOption(
			new RO_Option_CheckBox(
				'deleteImgs',
				'1',
				__('Delete image files from server and Media Library when deleting post.','PhotoQ')
			)
		);

		$this->registerOption(
			new RO_Option_CheckBox(
				'enableBatchUploads',
				'1',
				__('Enable Batch Uploads.','PhotoQ')
			)
		);

		$statusArray = array("draft", "private", "publish");
		$postStatus = new RO_Option_DropDownList(
				 'qPostStatus',
				 'publish',
				 __('This is the default status of posts posted via PhotoQ.','PhotoQ')
		);
		$postStatus->populate(array_combine($statusArray,$statusArray));
		$this->registerOption($postStatus);
		
		$postType = new RO_Option_DropDownList(
				 'qPostType',
				 'post',
				 __('This is the post type for posts posted via PhotoQ.','PhotoQ')
		);
		$this->registerOption($postType);				
		
		
		$this->registerOption(
			new RO_Option_AuthorDropDownList(
				 'qPostAuthor',
				 '1',
				 __('PhotoQ will fall back to this author if no author can be determined by any other means. This is for example the case if photos are automatically added to the queue through cronjobs.','PhotoQ')
			)
		);
		
		$this->registerOption(
			new RO_Option_CategoryDropDownList(
				 'qPostDefaultCat',
				 '1',
				 __('This is the default category for posts posted via PhotoQ.','PhotoQ')
			)
		);
		
		$this->registerOption(
			new RO_Option_TextField(
				 'qPostDefaultTags',
				 '',
				 __('Every post posted via PhotoQ has these default tags:','PhotoQ')
			)
		);
		
		$roleOptions = new RO_Option_Composite('specialCaps','','','<table><tr>','</tr></table>');
		$roleOptions->addChild(
			new PhotoQ_Option_RoleOption(
				'editorCaps','editor',
				array('use_primary_photoq_post_button','use_secondary_photoq_post_button','reorder_photoq'),
				__('Editor','PhotoQ'),
				'<td>',
				'</td>'
			)
		);
		$roleOptions->addChild(
			new PhotoQ_Option_RoleOption(
				'authorCaps','author',
				array('use_primary_photoq_post_button','use_secondary_photoq_post_button','reorder_photoq'),
				__('Author','PhotoQ'),
				'<td>',
				'</td>'
			)
		);
		
		$this->registerOption($roleOptions);
		
		/*$this->registerOption(
			new PhotoQ_Option_TaxonomyCheckBoxList(
				'taxonomies',
				array('category')
			)
		);*/
		
		
				//watermark options
		$watermark = new RO_Option_Composite('watermarkOptions');
		$watermarkPosition = new RO_Option_RadioButtonList(
				'watermarkPosition',
				'BL',
				'',
				'<tr valign="top"><th scope="row">'. __('Position','PhotoQ'). ': </th><td>',
				'</td></tr>'
		);
		$valueLabelArray = array(
			'BR' => __('Bottom Right','PhotoQ'),
			'BL' => __('Bottom Left','PhotoQ'),
			'TR' => __('Top Right','PhotoQ'),
			'TL' => __('Top Left','PhotoQ'),
			'C' => __('Center','PhotoQ'),
			'R' => __('Right','PhotoQ'),
			'L' => __('Left','PhotoQ'),
			'T' => __('Top','PhotoQ'),
			'B' => __('Bottom','PhotoQ'),
			'*'  => __('Tile','PhotoQ')
		);
		$watermarkPosition->populate($valueLabelArray);
		$watermark->addChild($watermarkPosition);
		
		$watermark->addChild(
			new RO_Option_TextField(
				'watermarkOpacity',
				'100',
				'',
				'<tr valign="top"><th scope="row">'. __('Opacity','PhotoQ'). ': </th><td>',
				'%</td></tr>',
				'2'
			)
		);
		
		$watermark->addChild(
			new RO_Option_TextField(
				'watermarkXMargin',
				'20',
				__('left/right','PhotoQ'). ':',
				'<tr valign="top"><th scope="row">'. __('Margins','PhotoQ'). ': </th><td>',
				'px, ',
				'2',
				'2'
			)
		);
		
		$watermark->addChild(
			new RO_Option_TextField(
				'watermarkYMargin',
				'20',
				__('top/bottom', 'PhotoQ'). ':',
				'',
				'px<br/>('. __('Values smaller than one are interpreted as percentages instead of pixels.','PhotoQ'). ')</td></tr>',
				'2',
				'2'
			)
		);
		
		$this->registerOption($watermark);
		
		//exif related settings
		//first the reorderable list of discovered exif tags
		$exifTags = new RO_Option_ReorderableList('exifTags');
		if($tags = get_option( "wimpq_exif_tags" )){
			foreach($tags as $key => $value){
				$exifTags->addChild(new PhotoQ_Option_ExifTagOption($key, $value));
			}
		}
		//localize strings
		$exifTags->localizeStrings(array(
				"selectedListLabel" => __('selected', 'PhotoQ'),
				"deselectedListLabel" => __('deselected', 'PhotoQ')
			)
		);
		$this->registerOption($exifTags);
		
		//now the exif display options
		$exifDisplayOptions = new RO_Option_Composite('exifDisplay');
		$exifDisplayOptions->addChild(
			new RO_Option_TextField(
				'exifBefore',
				esc_attr('<ul class="photoQExifInfo">'),
				'',
				'<table class="optionTable"><tr><td>'. __('Before List','PhotoQ'). ': </td><td>',
				sprintf(__('Default is %s','PhotoQ'), '<code>'.esc_attr('<ul class="photoQExifInfo">').'</code>') .'</td></tr>',
				'30'
			)
		);
		$exifDisplayOptions->addChild(
			new RO_Option_TextField(
				'exifAfter',
				esc_attr('</ul>'),
				'',
				'<tr><td>'. __('After List','PhotoQ'). ': </td><td>',
				sprintf(__('Default is %s','PhotoQ'), '<code>'.esc_attr('</ul>').'</code>') .'</td></tr>',
				'30'
			)
		);
		$exifDisplayOptions->addChild(
			new RO_Option_TextField(
				'exifElementBetween',
				'',
				'',
				'<tr><td>'. __('Between Elements','PhotoQ'). ': </td><td>',
				'</td></tr>',
				'30'
			)
		);
		$exifDisplayOptions->addChild(
			new RO_Option_TextArea(
				'exifElementFormatting',
				esc_attr('<li class="photoQExifInfoItem"><span class="photoQExifTag">[key]:</span> <span class="photoQExifValue">[value]</span></li>'),
				'',
				'<tr><td>'. __('Element Formatting','PhotoQ'). ': </td><td>
				<span class="setting-description">'
				.sprintf(__('You can specify the HTML that should be printed for each element here. Two shortags %1$s and %2$s are available. %1$s is replaced with the name of the EXIF tag, %2$s with its value. Here is an example, showing the default value: %3$s', 'PhotoQ'),'[key]','[value]','<code>'.esc_attr('<li class="photoQExifInfoItem"><span class="photoQExifTag">[key]:</span> <span class="photoQExifValue">[value]</span></li>').'</code>').'
				</span></td></tr><tr><td/><td>',
				'</td></tr></table>',
				2, 75
			)
		);
		$this->registerOption($exifDisplayOptions);
		
		
		//now the iptc copyright options
		$iptcCopyrightOptions = new RO_Option_Composite('iptcCopyright');
		$iptcCopyrightOptions->addChild(
			new RO_Option_TextField(
				'iptcCreditTag',
				'',
				'',
				'<table class="optionTable"><tr><td>'. __('Credit Tag','PhotoQ'). ': </td><td>',
				sprintf(__('E.g., %s','PhotoQ'), '<code>'.site_url().'</code>') .'</td></tr>',
				'50'
			)
		);
		$iptcCopyrightOptions->addChild(
			new RO_Option_TextField(
				'iptcSourceTag',
				'',
				'',
				'<tr><td>'. __('Source Tag','PhotoQ'). ': </td><td>',
				sprintf(__('E.g., %s','PhotoQ'), '<code>John Doe</code>') .'</td></tr>',
				'50'
			)
		);
		$iptcCopyrightOptions->addChild(
			new RO_Option_TextField(
				'iptcCopyrightTag',
				'',
				'',
				'<tr><td>'. __('Copyright Tag','PhotoQ'). ': </td><td>',
				sprintf(__('E.g., %s','PhotoQ'), '<code>Copyright John Doe, all rights reserved</code>') .'</td></tr>',
				'50'
			)
		);
		$iptcCopyrightOptions->addChild(
			new RO_Option_TextField(
				'iptcSpecialInstructionsTag',
				'',
				'',
				'<tr><td>'. __('Special Instructions Tag','PhotoQ'). ': </td><td>',
				sprintf(__('E.g., %s','PhotoQ'), 
					'<code>'.
					sprintf(__('For licensing assistance contact John Doe at %s','PhotoQ'), 
						get_bloginfo('admin_email')
					).
					'</code>'
				) .'</td></tr></table>',
				'50'
			)
		);
		$this->registerOption($iptcCopyrightOptions);
		
		
		
		//overwrite default options with saved options from database
		$this->load();

		//populate lists of image sizes that depend on runtime stuff and cannot be populated before
		$this->_populateAllViews();
		
		//check for existence of cache directory
		//convert backslashes (windows) to slashes
		$cleanAbs = str_replace('\\', '/', ABSPATH);
		$this->addTest( new RO_Validation_DirExists(
			array($this,'queueValidationError'),
			preg_replace('#'.$cleanAbs.'#', '', $this->getCacheDir()), 
			__('Cache Directory not found','PhotoQ'). ': ')
		);
		$this->addTest( new RO_Validation_FileWritable(
			array($this,'queueValidationError'),
			preg_replace('#'.$cleanAbs.'#', '', $this->getCacheDir()), 
			__('Cache Directory not writeable','PhotoQ'). ': ')
		);
	}

	/**
	 * Helper function to populate all currently registered views
	 * @return unknown_type
	 */	
	function _populateAllViews(){
		$numKids = $this->_options['views']->countChildren();
		$imgSizeNames = $this->getImageSizeNames();
		for($i = 0; $i < $numKids; $i++){
			$currentView = $this->_options['views']->getChild($i);
			$currentView->populate($imgSizeNames, !$this->_imageDirs->isOriginalHidden());
		}
	}
	
	/**
	 * Helper function to unpopulate all currently registered views
	 * @return unknown_type
	 */	
	private function _unpopulateAllViews(){
		$numKids = $this->_options['views']->countChildren();
		for($i = 0; $i < $numKids; $i++){
			$currentView = $this->_options['views']->getChild($i);
			$currentView->unpopulate();
		}
	}
	
	/**
	 * Determine whether the view with the given name is managed by photoq, i.e. its settings
	 * is not 'none'.
	 * @param $viewName			string the view to check
	 * @return boolean
	 */
	function isManaged($viewName){
		return $this->getValue($viewName . 'View-type') !== 'none';
	}
	
	function onCronImportFTPUploadsToQueue(){
		return !is_multisite() && $this->getValue('enableFtpUploads') && $this->getValue('cronFtpToQueue');
	}
	
	/**
	 * initialize stuff that depends on runtime configuration so that 
	 * what is displayed represents the changes from last update.
	 */
	function initRuntime()
	{
		//populate lists of image sizes that depend on runtime stuff and cannot be populated before
		$this->_unpopulateAllViews();
		$this->_populateAllViews();
		
		//$this->_populateTaxonomies();
		
		//populate post types
		$this->_options['qPostType']->removeChildren();
		$types = get_post_types( array( 'exclude_from_search' => false ));
		$types = array_diff($types, array('page','attachment'));
		$this->_options['qPostType']->populate(array_combine($types,$types));
		
		
		//test for presence of imageMagick
		$imagemagickTest = new PhotoQ_Option_ImageMagickPathCheckInputTest(array($this,'showImageMagickValError'));
		$imagemagickTest->validate($this->_options['imagemagickPath']);
	}
	
	function showImageMagickValError($valError){
		$this->_options['imagemagickPath']->setTextAfter('<br/>'. $valError);
	}
	
	/**
	 * Callback function that is called whenever a new image size is added in the PhotoQ Settings.
	 * @param $name	String	the name of the new image size
	 * @return true on success, false on failure
	 */
	function addImageSizeCallback($name){
		$imageSizes = $this->_options['imageSizes'];
		if($name != PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER && 
			!array_key_exists($name, $imageSizes->getValue())){
			return true;
		}else{
			add_settings_error('wimpq-photoq', 'component-exists',
				__('Please choose another name, an entry with this name already exists.', 'PhotoQ'), 'error');
			return false;
		}
	}
	
	function appendImageSizesWithWatermark($changedSizes){
		return array_unique(array_merge($changedSizes, $this->getImageSizeNamesWithWatermark()));
	}
	
 	function shouldRebuild($changedSizes){
		return !empty($changedSizes) || $this->hasChanged(array('views','exifTags','exifDisplay','originalFolder'));
	}
	
	/**
	 * Callback function that is called whenever a image size is deleted in the PhotoQ Settings.
	 * @param $name	String	the name of the image size to be deleted
	 * @return true on success, false on failure
	 */
	function delImageSizeCallback($name)
	{
		$imageSizeDir = $this->getImgDir() . $name;
		//remove corresponding dirs from server
		if(!file_exists($imageSizeDir) || PhotoQHelper::recursiveRemoveDir($imageSizeDir)){
			return true;
		}else{
			add_settings_error('wimpq-photoq', 'remove-imagesize-failed',
					sprintf(__('Could not remove image size. The required directories in %s could not be removed. Please check your settings.', 'PhotoQ'), $imageSizeDir), 'error');
			return false;
		}
	}
	
	/**
	 * Callback function that is called whenever a new view is added in the PhotoQ Settings.
	 * @param $name	String	the name of the new view
	 * @return true on success, false on failure
	 */
	function addViewCallback($name, $allowDuplicates = false){
		//do not add duplicates
		$views = $this->_options['views'];
		if(!$allowDuplicates && array_key_exists($name, $views->getValue())){
			add_settings_error(
				'wimpq-photoq', 'view-exists',
				sprintf(__('Please choose another name, a view with name "%s" already exists.', 'PhotoQ'), $name), 
				'error'
			);	
			return false;
		}
		//do not add if a meta field with same name exists
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fieldNames = $fieldTable->getFieldNames();
		if(in_array($name, $fieldNames)){
			add_settings_error(
				'wimpq-photoq', 'field-exists',
				sprintf(__('Please choose another name, a meta field with name "%s" already exists.', 'PhotoQ'), $name), 
				'error'
			);	
			return false;
		}
		//add a custom field with the same name to all published photos. this field will hold the view.
		$this->_db->addFieldToPublishedPosts($name);
		
		return true;
	}
	
	/**
	 * Callback function that is called whenever a view is deleted in the PhotoQ Settings.
	 * @param $name	String	the name of the view to be deleted
	 * @return true on success, false on failure
	 */
	function delViewCallback($name)
	{
		delete_post_meta_by_key($name);
		//delete the corresponding custom fields from published photoq posts
		//$this->_db->deleteFieldFromPublishedPosts($name);
		return true;
	}
	

	/**
	 * Returns the current qdir.
	 * @return unknown_type	String the current qdir.
	 */	
	function getQDir(){
		return $this->getImgDir().'qdir/';
	}
	
	/**
	 * Returns the cache directory used by phpThumb. This is now fixed to wp-content/photoQCache.
	 *
	 * @return string	The cache directory.
	 */
	function getCacheDir(){
		return str_replace('\\', '/', WP_CONTENT_DIR) . '/photoQCache/';
	}
	
	/**
	 * This is a folder where the user can place his/her own presets
	 * @return string
	 */
	function getMyPresetsDir(){
		return str_replace('\\', '/', WP_CONTENT_DIR) . '/myPhotoQPresets/';
	}
	
	function getPresetsDir(){
		return PHOTOQ_PATH.'presets/';
	}
	
	public function getImgDir(){
		$uploads = wp_upload_dir();
		
		if ($uploads['error'] !== false)
			throw new PhotoQ_Error_Exception($upload['error']);
			
		return str_replace('\\',  '/', rtrim($uploads['basedir'], '/\\')) . '/';
	}
	
	
	
	function getFTPDir(){
		//for windows directories (e.g. c:/) we don't want a first slash
		$firstSlash = '/';
		if(preg_match('/^[a-zA-Z]:/', $this->getValue('ftpDir')))
			$firstSlash = '';
		return $firstSlash.trim($this->getValue('ftpDir'), '\\/').'/';
	}
	
	
	function getPresetCategories()
	{
		return $this->_presetCategories;
	}
	
	
	/**
	 * Returns an array containing all image sizes.
	 *
	 * @return array	the names of all registered imageSizes
	 */
	function getImageSizeNames()
	{
		return array_keys($this->getValue('imageSizes'));
	}
	
	/**
	 * Returns an array containing all view names.
	 *
	 * @return array	the names of all registered views
	 */
	function getViewNames()
	{
		return array_keys($this->getValue('views'));
	}
	
	/**
	 * Returns an array containing names of views that changed during last update.
	 *
	 * @return array	the names of all changed views
	 */
	function getChangedViewNames(array $changedSizes = array(), $updateExif = false, $updateOriginalFolder = false){
		//get all the views that changed directly during the last update
		$changedViewNames = $this->_getChangedExpCompElements('views');
		$changedViewNames = $this->_appendIndirectlyChangedViews($changedViewNames, $changedSizes, $updateExif, $updateOriginalFolder);
		return array_unique($changedViewNames);
	}
	
	private function _appendIndirectlyChangedViews(array $changedViewNames = array(), array $changedSizes = array(), $updateExif = false, $updateOriginalFolder = false){
		$changeInducingShorttags = $this->_buildListOfChangeInducingTags($changedSizes,$updateExif,$updateOriginalFolder);
		//go through all views and check whether anything relevant to the view changed (e.g. the size of images
		//used inside the view) and an update of the view is necessary even if it didn't change itself.
		foreach($this->getViewNames() as $currentViewName){
			if($this->_shouldAppend($currentViewName, $changedViewNames, $changedSizes, $updateExif, $changeInducingShorttags))
				$changedViewNames[] = $currentViewName;
		}
		return $changedViewNames;
	}
	
	/**
	 * Builds the list of shorttags that induce a view change of any view containing them
	 * @param $changedSizes			Array		the image sizes that changed in last update
	 * @param $updateExif			boolean		whether exif data changed in last update
	 * @param $updateOriginalFolder	boolean		whether original folder chagned in last update
	 * @return 						Array		list of tags to be checked
	 */
	private function _buildListOfChangeInducingTags($changedSizes, $updateExif, $updateOriginalFolder){
		$tags2Test = array();
		if($updateExif) $tags2Test[] = 'exif';
		if($updateOriginalFolder){ 
			$tags2Test[] = 'imgUrl|original';
			$tags2Test[] = 'imgPath|original';
		}
		foreach ($changedSizes as $currentSize){
			$tags2Test[] = 'imgWidth|'.$currentSize;
			$tags2Test[] = 'imgHeight|'.$currentSize;
		}
		return $tags2Test;
	}
	
	private function _shouldAppend($currentViewName, array $changedViewNames, array $changedSizes, $updateExif, array $changeInducingShorttags){
		if(in_array($currentViewName, $changedViewNames)){
			return false;
		}else{
			if($this->_isFreeformViewAndContainsChangeInducingShorttag($currentViewName, $changeInducingShorttags)){
				return true;
			}elseif($this->_isSingleViewAndCorrespondingSizeChanged($currentViewName, $changedSizes)){
				return true;
			}elseif($this->_isImgLinkAndSourceOrTargetSizeChanged($currentViewName, $changedSizes)){ 
				return true;
			}elseif($this->_isContentViewWithUpdatedInlinedExif($currentViewName, $updateExif)){
				return true;
			}else{
				return false;
			}
		}
	}
	
	private function _isFreeformViewAndContainsChangeInducingShorttag($currentViewName, array $changeInducingShorttags){
		return $this->getValue($currentViewName . 'View-type') == 'freeform' &&
				PhotoQHelper::containsAnyOfTheseShorttags(
					$this->getValue($currentViewName . 'View-freeform'),
					$changeInducingShorttags
				);
	}
	
	private function _isSingleViewAndCorrespondingSizeChanged($currentViewName, array $changedSizes){
		return $this->getValue($currentViewName . 'View-type') == 'single' &&
				in_array($this->getValue($currentViewName . 'View-singleSize'), $changedSizes);
	}
	
	private function _isImgLinkAndSourceOrTargetSizeChanged($currentViewName, array $changedSizes){
		return $this->getValue($currentViewName . 'View-type') == 'imgLink' &&
				(
					in_array($this->getValue($currentViewName . 'View-imgLinkSize'), $changedSizes) ||
					in_array($this->getValue($currentViewName . 'View-imgLinkTargetSize'), $changedSizes)
				);			
	}
	
	private function _isContentViewWithUpdatedInlinedExif($currentViewName, $updateExif){
		return $currentViewName == 'content' && $updateExif && $this->getValue('inlineExif');
	}
	
	
	/**
	 * Returns an array containing names of image sizes that changed during last update.
	 *
	 * @return array	the names of all changed imageSizes
	 */
	function getChangedImageSizeNames()
	{
		return $this->_getChangedExpCompElements('imageSizes');
	}
	
	/**
	 * Low level function that returns the new elements of an expandable composite plus all the elements that changed
	 * during the last update.
	 * @param $containerName String	the name of the expandable composite option
	 * @return array	containing names of elements that changed.
	 */
	function _getChangedExpCompElements($containerName){
		if($this->hasChanged($containerName)){
			//get new elements
			if(!is_array($this->getOldValues($containerName)))
				$oldVals = array();
			else
				$oldVals = $this->getOldValues($containerName);
			
			$oldNames = array_keys($oldVals);
			$currentNames = array_keys($this->getValue($containerName));
			$currentNames = array_filter($currentNames, array($this,'_filterDefaultNames_'.$containerName));
			$newNames = array_diff($currentNames,$oldNames);
			$containerOption = $this->_options[$containerName];
			return array_unique(array_merge($newNames,$containerOption->getChangedChildrenNames()));
		}else
			return array();	
	}
	
	/**
	 * Returns false if the name given is a default name.
	 * @param $name
	 * @return unknown_type
	 */
	function _filterDefaultNames_imageSizes($name){
		$defaultNames = array(PhotoQ_File_ImageDirs::MAIN_IDENTIFIER, 
			PhotoQ_File_ImageDirs::THUMB_IDENTIFIER);
		return !in_array($name, $defaultNames);
	}
	/**
	 * Returns false if the name given is a default name.
	 * @param $name
	 * @return unknown_type
	 */
	function _filterDefaultNames_views($name){
		$defaultNames = array('content', 'excerpt');
		return !in_array($name, $defaultNames);
	}
	
	
	/**
	 * Returns an array containing names of imagesizes that have a watermark.
	 * @return array
	 */
	function getImageSizeNamesWithWatermark(){
		return $this->_options['imageSizes']->getImageSizeNamesWithWatermark();
	}
	
	/**
	 * Goes through all exif tags that changed. Returns two arrays, the first
	 * one containing the names of tags that got added to tagfromexif, the
	 * the second one containing the names of those who got deleted.
	 * @return unknown_type
	 */
	function getAddedDeletedTagsFromExif(){
		$changedTags = $this->_options['exifTags']->getChildrenWithAttribute();
		$added = array();
		$deleted = array();
		foreach($changedTags as $tag){
			//get the checkbox that determines tagFromExif status
			$checkBox = $tag->getOptionByName($tag->getName().'-tag');
			if($checkBox->getValue() == 1)
				$added[] = $tag->getName();
			else
				$deleted[] = $tag->getName();			
		}
		return array($added, $deleted);
	}
	
	
	function getOldValues($containerName)
	{
		return $this->_options[$containerName]->_oldValues;
	}
	
	
	/**
	 * Callback called whenever an error fails validation
	 * @param $valError string the error message
	 * @return unknown_type
	 */
	function queueValidationError($valError)
	{
		add_settings_error('wimpq-photoq', 'setting-validation-error', $valError, 'error');
	}
	
	function renderListOfPresets(){
		
		
		$presetDirs = array(
					$this->getMyPresetsDir() => __('My PhotoQ Presets', 'PhotoQ')
		);
		
		foreach($this->_presetCategories as $key => $value){
			$presetDirs[$this->getPresetsDir().$key.'/'] = $value;
		}
		
		_e('Choose your Theme Preset: ', 'PhotoQ');
		echo '<select name="presetFile" id="presetFile">';
		
		foreach($presetDirs as $path => $displayName){
		
			$presetFilePaths = PhotoQHelper::getMatchingDirContent($path,'#\.xml$#');
			if(count($presetFilePaths))
				echo '<optgroup label="'.$displayName.'">';
			foreach ($presetFilePaths as $presetPath){
				echo '<option value="'.$presetPath.'">'.PhotoQHelper::niceDisplayNameFromFileName(basename($presetPath)).'</option>';
			}
			if(count($presetFilePaths))
				echo '</optgroup>';

		}
		echo '</select>';
	}
	
	/**
	 * Show array of options as rows of the table
	 * @param $optionArray
	 * @return unknown_type
	 */
	function showOptionArray($optionArray){
		foreach ($optionArray as $optName => $optLabel){
			echo '<tr valign="top">'. PHP_EOL;
			echo '   <th scope="row">'.$optLabel.'</th>'.PHP_EOL.'   <td>';
			$this->render($optName);
			echo '</td>'.PHP_EOL.'</tr>'. PHP_EOL;
		}
	}
	
	public function deleteElementFromComposite($compName, $elementName){
		$compOption = $this->_options[$compName];
		$compOption->removeChild($elementName);
	}
	
	/**
	 * Override the stored option with options given in the provided array
	 * @param unknown_type $optionArray
	 * @return unknown_type
	 */
	public function forceOptions(array $optionArray){
		$storedOptions = get_option($this->_optionsDBName);
		foreach($optionArray as $key => $val){
			if(!is_array($storedOptions) || array_key_exists($key, $storedOptions))
				$storedOptions[$key] = PhotoQHelper::arrayHtmlEntities($val);
		}
		update_option($this->_optionsDBName, $storedOptions);
			
		//reload to make the changes active
		$this->load();
	}
	
}