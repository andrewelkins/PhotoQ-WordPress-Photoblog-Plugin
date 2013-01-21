<?php

/**
 * This class deals with EXIF meta data embedded in the photos.
 *
 */
class PhotoQExif
{

	/**
	 * Get associative array with exif info from a photo
	 *
	 * @param string $path	Path to the photo.
	 * @return array		Exif info in associative array.
	 */
	public static function readExif($path)
	{
		$iptc = self::_readIPTC($path);

		//include and call the exifixer script
		require_once realpath(PHOTOQ_PATH.'lib/exif/exif.php');
		$fullexif = read_exif_data_raw($path, 0);
		//we now retain only the useful (whatever it means ;-) ) info
		$ifd0 = self::_filterUseless($fullexif['IFD0']);
		$subIfd = self::_filterUseless($fullexif['SubIFD']);
		$makerNote = $subIfd['MakerNote'];
		unset($subIfd['MakerNote']);
		$gps = self::_filterUseless($fullexif['GPS']);

		//bring all the arrays to single dimension
		$ifd0 = PhotoQHelper::flatten($ifd0);
		$subIfd = PhotoQHelper::flatten($subIfd);
		$makerNote = PhotoQHelper::flatten($makerNote);
		$gps = PhotoQHelper::flatten($gps);

		//and finally merge all of them into a single array
		$exif = array_merge($iptc, $ifd0, $subIfd, $makerNote, $gps);


		//update discovered tags
		self::_discoverTags($exif);

		list( , , $sourceImageType) = getimagesize($path);
		return apply_filters('wp_read_image_metadata', $exif, $path, $sourceImageType);
	}

	/**
	 * Creates the formatted exif list. Only tags selected in PhotoQ
	 * and that are present in the current photo are displayed.
	 * TagsFromExif are shown as links to the corresponding tag pages.
	 * @param $exif	the full exif data array of this post
	 * @param $tags the exif tags that are selected in photoq
	 * @param $tagsFromExif	the exif tags that were chosen as post_tags via tagFromExif
	 * @return string	formatted exif outpout in form of unordered html list
	 */
	public static function getFormattedExif($exif, $tags, $tagsFromExif, $displayNames, $displayOptions){
		if(!empty($tags) && !is_array($tags)){
			//is it a comma separated list?
			$tags = array_unique(explode(',',$tags));
		}
		if(!is_array($tags) || count($tags) < 1 ){
			//still nothing?
			$result = '';
		}else{
			$result = $displayOptions['before'];//'<ul class="photoQExifInfo">';
			$foundOne = false; //we don't want to print <ul> if there is no exif in the photo
			foreach($tags as $tag){
				if(is_array($exif) && array_key_exists($tag, $exif)){
					$foundOne = true;
					if(empty($displayOptions['elementFormatting']))//go with default
					$displayOptions['elementFormatting'] = '<li class="photoQExifInfoItem"><span class="photoQExifTag">[key]:</span> <span class="photoQExifValue">[value]</span></li>';
						
					$displayName = $tag;
					//do we need to display a special name
					if(!empty($displayNames[$tag]))
					$displayName = $displayNames[$tag];
						
					$value = $exif[$tag];
						
					//do we need a tag link?
					if(in_array($tag, $tagsFromExif)){
						//yes, so try to get an id and then the link
						$term = get_term_by('name', $value, 'post_tag');
						if($term)
						$value = '<a href="'.get_tag_link($term->term_id).'">'.$value.'</a>';
					}

					$result .= PhotoQHelper::formatShorttags($displayOptions['elementFormatting'], array('key' => $displayName, 'value' => $value));
					$result .= $displayOptions['elementBetween'];
				}
			}
			//remove last occurrence of elementBetween
			$result = preg_replace('/'.preg_quote($displayOptions['elementBetween']).'$/','',$result);
			$result .= $displayOptions['after'];//'</ul>';
				
				
			if(!$foundOne)
			$result = '';
		}
		return $result;
	}


	private static function _discoverTags($newTags){
		$oldTags = get_option( "wimpq_exif_tags" );
		if($oldTags !== false){
			$discovered = array_merge($oldTags, $newTags);
			ksort($discovered, SORT_STRING);
			update_option( "wimpq_exif_tags", $discovered);
		}else
		add_option("wimpq_exif_tags", $newTags);
			
	}

	/**
	 * Recursively removes entries containing ':unknown' in key from input array.
	 *
	 * @param array $in the input array
	 * @return array	the filtered array
	 */
	private static function _filterUseless($in){
		$out = array();
		if(is_array($in)){
			foreach ($in as $key => $value){
				if(strpos($key,'unknown:') === false && !in_array($key,self::_getUselessTagNames()))
				if(is_array($value))
				$out[$key] = self::_filterUseless($value);
				else
				$out[$key] = self::_sanitizeExifValue($value);
			}
		}
		return $out;
	}

	/**
	 * This return a list of tags that are either not implemented correctly in exifixer,
	 * that are added by exifixer and not needed or that contain no useful information (e.g.
	 * only offsets inside the TIFF header or info i deem unlikely to be useful to my users).
	 *
	 * @return unknown
	 */
	private static function _getUselessTagNames()
	{
		return array(
		'Bytes',
		'CFAPattern',
		'ComponentsConfiguration',	
		'CustomerRender',			
		'ExifInteroperabilityOffset',
		'ExifOffset',
		'GPSInfo',
		'KnownMaker',
		'MakerNoteNumTags',
		'OwnerName',
		'RAWDATA',
		'Unknown',
		'UserCommentOld',
		'VerboseOutput',
		'YCbCrPositioning'
		);
	}

	private static function _sanitizeExifValue($value)
	{
		return preg_replace('#[^(a-zA-Z0-9_\s\.\:\/\,\;\-)]#','',$value);
	}

	/**
	 * Reads the IPTC metadata from the file with the path given
	 * @param $path
	 * @return unknown_type
	 */
	private static function _readIPTC($path)
	{
		$result = array();

		//done according to wp-admin/includes/image.php:wp_read_image_metadata() with
		//exception of additional remove of problematic chars.

		$iptc = self::_parseIPTCInfo($path);
		foreach (self::_getIPTCTags() as $key => $value) {
			if (!empty($iptc[$key][0]))
				$result[$value] = self::_removeProblematicChars(utf8_encode(trim(implode(", ", $iptc[$key]))));
		}
		return $result;
	}
	
	private static function _parseIPTCInfo($path){
		if (is_callable('iptcparse')) {
			@getimagesize($path, $info);
			if ( !empty($info['APP13']) )
				return @iptcparse($info['APP13']);
		}
		return array();
	}

	/**
	 * Removes characters like french accents or german umlauts, as well as ' that created problems at least on
	 * my machine.
	 * @param string $in
	 * @return string
	 */
	private static function _removeProblematicChars($in){
		$out = preg_replace('/[\']/', ' ', $in);
		$out = iconv('UTF-8', 'ASCII//TRANSLIT', $out); // Ž e.g. becomes e'
		return preg_replace('/[\']/', '', $out);
	}

	/**
	 * Returns list of IPTC-NAA IIM fields and their identifier
	 * @return unknown_type
	 */
	private static function _getIPTCTags()
	{
		//List taken from http://www.ozhiker.com/electronics/pjmt/library/list_contents.php4?show_fn=IPTC.php
		// Application Record
		return array(
			'2#000' => 'Record Version',
			'2#003' => 'Object Type Reference',
			'2#005' => 'Object Name (Title)',
			'2#007' => 'Edit Status',
			'2#008' => 'Editorial Update',
			'2#010' => 'Urgency',
			'2#012' => 'Subject Reference',
			'2#015' => 'Category',
			'2#020' => 'Supplemental Category',
			'2#022' => 'Fixture Identifier',
			'2#025' => 'Keywords',
			'2#026' => 'Content Location Code',
			'2#027' => 'Content Location Name',
			'2#030' => 'Release Date',
			'2#035' => 'Release Time',
			'2#037' => 'Expiration Date',
			'2#035' => 'Expiration Time',
			'2#040' => 'Special Instructions',
			'2#042' => 'Action Advised',
			'2#045' => 'Reference Service',
			'2#047' => 'Reference Date',
			'2#050' => 'Reference Number',
			'2#055' => 'Date Created',
			'2#060' => 'Time Created',
			'2#062' => 'Digital Creation Date',
			'2#063' => 'Digital Creation Time',
			'2#065' => 'Originating Program',
			'2#070' => 'Program Version',
			'2#075' => 'Object Cycle',
			'2#080' => 'By-Line (Author)',
			'2#085' => 'By-Line Title (Author Position)',
			'2#090' => 'City',
			'2#092' => 'Sub-Location',
			'2#095' => 'Province/State',
			'2#100' => 'Country/Primary Location Code',
			'2#101' => 'Country/Primary Location Name',
			'2#103' => 'Original Transmission Reference',
			'2#105' => 'Headline',
			'2#110' => 'Credit',
			'2#115' => 'Source',
			'2#116' => 'Copyright Notice',
			'2#118' => 'Contact',
			'2#120' => 'Caption/Abstract',
			'2#122' => 'Caption Writer/Editor',
			'2#125' => 'Rasterized Caption',
			'2#130' => 'Image Type',
			'2#131' => 'Image Orientation',
			'2#135' => 'Language Identifier',
			'2#150' => 'Audio Type',
			'2#151' => 'Audio Sampling Rate',
			'2#152' => 'Audio Sampling Resolution',
			'2#153' => 'Audio Duration',
			'2#154' => 'Audio Outcue',
			'2#200' => 'ObjectData Preview File Format',
			'2#201' => 'ObjectData Preview File Format Version',
			'2#202' => 'ObjectData Preview Data'
			);
	}

	
	
	/**
	 * Follows the example given in the php manual on iptcembed. Alternatively,
	 * Laura Cotterman's imagemetadata.php script gives an analog implementation.
	 * 
	 * @see http://php.net/manual/en/function.iptcembed.php
	 * @see http://www.imagemetadata.com/index.php
	 * 
	 * @param unknown_type $originalPath
	 * @param unknown_type $thumbPath
	 */
	public static function addIPTCInfo($originalPath, $thumbPath) {
		$iptcOld = self::_parseIPTCInfo($originalPath);
		$iptcOld = self::_addCopyrightInfo($iptcOld);
		self::_writeIPTC($iptcOld, $thumbPath);
	}
	
	private static function _addCopyrightInfo($iptc){
		$oc = PhotoQ_Option_OptionController::getInstance();
		
		$copyrightFields = array(
			'2#110' => $oc->getValue('iptcCreditTag'),
			'2#115' => $oc->getValue('iptcSourceTag'),
			'2#116' => $oc->getValue('iptcCopyrightTag'),
			'2#040' => $oc->getValue('iptcSpecialInstructionsTag')
		);
		
		foreach($copyrightFields as $key => $val){
			if(empty($iptc[$key][0]) && !empty($val)){
				$iptc[$key][0] = $val;
			}elseif($key == '2#040' && !empty($val))
				$iptc[$key][0] .= $val;
		}
		
		return $iptc;
	}
	
	private static function _writeIPTC($iptc, $path){
		$content = iptcembed(self::_makeIPTCString($iptc), $path);
		$fp = fopen($path, "wb");
		fwrite($fp, $content);
		fclose($fp);
	}
	
	private static function _makeIPTCString($iptcArray){
		$iptcString = "";
		// Making the new IPTC string
		foreach (array_keys($iptcArray) as $s){
			// Finds the IPTC numbers
			$tag = str_replace("2#", "", $s);
			// Creating the string
			$c = count ($iptcArray[$s]);
			for ($i=0; $i<$c; $i++){
				$iptcString .= self::_IPTCMakeTag(2, $tag, $iptcArray[$s][$i]);
			}
		}
		return $iptcString;
	}

	/**
	 * Corresponds to iptc_make_tag() function by Thies C. Arntzen
	 * @see http://php.net/manual/en/function.iptcembed.php
	 * @param $rec 		Application record. (We’re working with #2)
	 * @param $data 	Index. (120 for caption, 115 for contact, etc.)
	 * @param $value 	Make sure this is within the length constraints of the IPTC IIM specification
	 */
	private static function _IPTCMakeTag($rec, $data, $value)
	{
		$length = strlen($value);
		$retval = chr(0x1C) . chr($rec) . chr($data);
		if($length < 0x8000){
			$retval .= chr($length >> 8) .  chr($length & 0xFF);
		}else{
			$retval .= 	chr(0x80) .
						chr(0x04) .
						chr(($length >> 24) & 0xFF) .
						chr(($length >> 16) & 0xFF) .
						chr(($length >> 8) & 0xFF) .
						chr($length & 0xFF);
		}
		return $retval . $value;
	}
	
	
}
?>