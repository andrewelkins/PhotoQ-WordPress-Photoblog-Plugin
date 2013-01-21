<?php
class PhotoQ_Option_ImageSizeOption extends RO_Option_Composite
{
	
	/**
	 * Default width of Image size.
	 *
	 * @access private
	 * @var integer
	 */
	var $_defaultWidth;
	
	/**
	 * Default height of Image size.
	 *
	 * @access private
	 * @var integer
	 */
	var $_defaultHeight;
	
	
	function __construct($name, $defaultValue = '1', $defaultWidth = '700', $defaultHeight = '525')
	{
		parent::__construct($name, $defaultValue);
		
		$this->_defaultWidth = $defaultWidth;
		$this->_defaultHeight = $defaultHeight;
		
		$this->_buildRadioButtonList();
		
		
		$this->addChild(
			new RO_Option_TextField(
				$this->_name . '-imgQuality',
				'95',
				'',
				'<tr valign="top"><th scope="row">'.__('Image Quality','PhotoQ').': </th><td>',
				'%</td></tr>',
				'2'
			)
		);
		
		$this->addChild(
			new RO_Option_CheckBox(
				$this->_name . '-watermark',
				'0',
				__('Add watermark to all images of this size.','PhotoQ'),
				'<tr valign="top"><th scope="row">'.__('Watermark','PhotoQ').':</th><td>',
				'</td></tr>'
			)
		);
		
		$this->addChild(
			new RO_Option_CheckBox(
				$this->_name . '-writeIPTC',
				'0',
				__('Write IPTC metadata to all images of this size.','PhotoQ'),
				'<tr valign="top"><th scope="row">'.__('IPTC','PhotoQ').':</th><td>',
				'</td></tr>'
			)
		);
		
	}
	
	
	
	function _buildRadioButtonList()
	{
		$imgConstr = new RO_Option_RadioButtonList(
				$this->_name . '-imgConstraint',
				'rect'
		);

		$maxDimImg = new RO_Option_RadioButton(
				'rect',
				__('Maximum Dimensions','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th>'
		);
		$maxDimImg->addChild(
			new RO_Option_TextField(
				$this->_name . '-imgWidth',
				$this->_defaultWidth,
				'',
				'<td>',
				__('px wide','PhotoQ').', ',
				'4',
				'5'
			)
		);
		$maxDimImg->addChild(
			new RO_Option_TextField(
				$this->_name . '-imgHeight',
				$this->_defaultHeight,
				'',
				'',
				__('px high','PhotoQ').' ',
				'4',
				'5'
			)
		);
		$maxDimImg->addChild(
			new RO_Option_CheckBox(
				$this->_name . '-zoomCrop',
				0,
				__('Crop to max. dimension','PhotoQ').'.&nbsp;)',
				'&nbsp;(&nbsp;',
				'</td></tr>'
			)
		);
		$imgConstr->addChild($maxDimImg);


		$smallestSideImg = new RO_Option_RadioButton(
				'side',
				__('Smallest side','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th>'
		);
		$smallestSideImg->addChild(
			new RO_Option_TextField(
				$this->_name . '-imgSide',
				'525',
				'',
				'<td>',
				'px</td></tr>',
				'4',
				'5'
			)
		);
		$imgConstr->addChild($smallestSideImg);

		$fixedWidthImg = new RO_Option_RadioButton(
				'fixed',
				__('Landscape Width','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th>'
		);
		$fixedWidthImg->addChild(
			new RO_Option_TextField(
				$this->_name . '-imgFixed',
				'525',
				'',
				'<td>',
				'px</td></tr>',
				'4',
				'5'
			)
		);
		$imgConstr->addChild($fixedWidthImg);

		$imgConstr->addChild(
			new RO_Option_RadioButton(
				'noResize',
				__('Original Size','PhotoQ').': ',
				'<tr valign="top"><th scope="row">',
				'</th><td>'.__('Keep original image size, don\'t resize','PhotoQ').'.</td></tr>'
			)
		);
		
		
		
		$this->addChild($imgConstr);
	}
	
	
	/**
	 * Returns boolean indicating whether this image size sports a watermark.
	 * @return boolean true if image size has watermark, false otherwise
	 */
	function hasWatermark(){
		$option = $this->getOptionByName($this->_name.'-watermark');
		return $option->getValue(); 
	}
 	
	
	

}
