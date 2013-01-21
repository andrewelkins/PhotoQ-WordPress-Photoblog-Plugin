<?php
class PhotoQ_Option_ViewOption extends RO_Option_Composite
{
	
	var $_mainID;
	var $_thumbID;

	/**
	 * PHP5 type constructor
	 */
	function __construct($name, $withNoneField = false)
	{
		parent::__construct($name);
		
		$this->_mainID = PhotoQ_File_ImageDirs::MAIN_IDENTIFIER;
		$this->_thumbID = PhotoQ_File_ImageDirs::THUMB_IDENTIFIER;
		
		//get the db object
		$this->_db = PhotoQ_DB_DB::getInstance();
		
		
		$this->_buildRadioButtonList($withNoneField);
	}
		
	
	function _buildRadioButtonList($withNoneField = false)
	{
		$viewType = new RO_Option_RadioButtonList(
				$this->_name . 'View-type',
				'single'
		);

		//gives the option to disable managing of this view with photoq
		if($withNoneField){
			$viewType->addChild(
				new RO_Option_RadioButton(
					'none',
					__('Empty, don\'t manage.','PhotoQ'),
					'<tr valign="top"><th scope="row">',
					'</th><td></td></tr>'
				)
			);
		}
		
		$singleImg = new RO_Option_RadioButton(
				'single',
				__('Single Photo','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th>'
		);
		$singleSize = new RO_Option_DropDownList(
				$this->_name . 'View-singleSize',
				$this->_mainID,
				'',
				'<td>',
				'</td></tr>'
		);
		$singleImg->addChild($singleSize);
		$viewType->addChild($singleImg);
		
		
		$imgLink = new RO_Option_RadioButton(
				'imgLink',
				__('Image Link','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th>'
		);
		$imgLinkSize = new RO_Option_DropDownList(
				$this->_name . 'View-imgLinkSize',
				$this->_thumbID,
				'',
				'<td>',
				__(' linking to ','PhotoQ')
		);		
		$imgLink->addChild($imgLinkSize);
		$imgLinkTargetSize = new RO_Option_DropDownList(
				$this->_name . 'View-imgLinkTargetSize',
				$this->_mainID,
				'',
				'',
				''
		);
		$imgLink->addChild($imgLinkTargetSize);
		
		$imgLink->addChild(
			new RO_Option_TextField(
				$this->_name . 'View-imgLinkAttributes',
				esc_attr('rel="lightbox"'),
				', '.__('link having following attributes','PhotoQ').': ',
				'',
				'<br />
				<span class="setting-description">'.__('Allows interaction with JS libraries such as Lightbox and Shutter Reloaded without modifying templates.','PhotoQ').'</span></td></tr>',
				'40'
			)
		);
		
		$viewType->addChild($imgLink);
		
		$freeform = new RO_Option_RadioButton(
			'freeform',__('Freeform', 'PhotoQ').': ',
			'<tr valign="top"><th scope="row">',
			'</th>'
		);
		$freeform->addChild(new RO_Option_TextArea(
			$this->_name .'View-freeform',
			'',
			'',
			'<td>',
			'<br/><span class="setting-description">'.sprintf(__('HTML as well as the following shorttags are allowed: %s, where %s has to be replaced with the name of the existing image size (e.g. %s or %s) that you want to use.','PhotoQ'), $this->_createFreeformShorttagList(), '"sizeName"', '"main"', '"original"').'</span></td></tr>',
			7, 100	
		));
		
		$viewType->addChild($freeform);
		
		$this->addChild($viewType);
		
	}
	
	/**
	 * Helper to create comma separated list of shorttags allowed in freeform views.
	 * @return string	the list of shorttags that are allowed
	 */
	function _createFreeformShorttagList(){
		$allowedShorttags = array('title', 'descr', 'exif');
		//add the meta fields and the sizes options 
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$allowedShorttags = array_merge(
			$allowedShorttags, 
			$fieldTable->getFieldNames(), 
			array('imgUrl|sizeName', 'imgPath|sizeName', 'imgWidth|sizeName', 'imgHeight|sizeName')
		);
		
		for($i = 0; $i<count($allowedShorttags); $i++)
			$allowedShorttags[$i] = '<code>['.$allowedShorttags[$i].']</code>';
		return implode(', ', $allowedShorttags);
	}
	
	/**
	 * Populate the lists of image sizes with the names of registered image sizes as key, value pair.
	 *
	 * @param array $imgSizeNames
	 * @access public
	 */
	function populate($imgSizeNames, $addOriginal = true)
	{
		//add the original as an option
		if($addOriginal)
			array_push($imgSizeNames, PhotoQ_File_ImageDirs::ORIGINAL_IDENTIFIER);
		
		$singleSize = $this->getOptionByName($this->_name .'View-singleSize');
		$singleSize->populate(array_combine($imgSizeNames,$imgSizeNames));
		
		$imgLinkSize = $this->getOptionByName($this->_name .'View-imgLinkSize');
		$imgLinkSize->populate(array_combine($imgSizeNames,$imgSizeNames));
		
		$imgLinkTargetSize = $this->getOptionByName($this->_name .'View-imgLinkTargetSize');
		$imgLinkTargetSize->populate(array_combine($imgSizeNames,$imgSizeNames));
		
	}
	
	/**
	 * Remove names of registered image sizes as key, value pair.
	 *
	 * @access public
	 */
	function unpopulate()
	{
		$singleSize = $this->getOptionByName($this->_name .'View-singleSize');
		$singleSize->removeChildren();
		$imgLinkSize = $this->getOptionByName($this->_name .'View-imgLinkSize');
		$imgLinkSize->removeChildren();
		$imgLinkTargetSize = $this->getOptionByName($this->_name .'View-imgLinkTargetSize');
		$imgLinkTargetSize->removeChildren();
		
	}

}
