<?php
class PhotoQHelper
{
	
	/**
	 * Checks whether a post is a photo post. A post is considered a photopost if it has a custom
	 * field called photoQPath.
	 *
	 * @param unknown $postID The id of the post to be checked
	 * @return boolean True if the post is photo post
	 * @access public
	 */
	public static function isPhotoPost($postID)
	{
		$photoQPath = get_post_meta($postID, 'photoQPath', true);
		if(empty($photoQPath)) return false;
		return true;
	}

	function createDir($path)
	{
		$created = true;
		if (!file_exists($path)) {
			//use built-in wp function -> we have same directory permissions
			//as standard wp created directories
			$created = wp_mkdir_p($path);
		}
		return $created;
	}

	function removeDir($path)
	{
		$removed = true;
		if (file_exists($path)) {
			$removed = rmdir($path);
		}
		return $removed;
	}

	/**
	 * Remove directory and all its content recursively.
	 *
	 * @param string $filepath
	 * @return boolean
	 */
	function recursiveRemoveDir($filepath)
	{
		if (is_dir($filepath) && !is_link($filepath))
		{
			if ($dh = opendir($filepath))
			{
				while (($sf = readdir($dh)) !== false)
				{
					if ($sf == '.' || $sf == '..')
					{
						continue;
					}
					if (!PhotoQHelper::recursiveRemoveDir($filepath.'/'.$sf))
					{
						$rmError = new PhotoQ_Error_Exception($filepath.'/'.$sf.' could not be deleted.');
						echo $rmError;//TODO change this, also include i18n
					}
				}
				closedir($dh);
			}
			return rmdir($filepath);
		}
		if(file_exists($filepath))
			return unlink($filepath);
		else
			return false;
	}

	function getArrayOfTagNames($postID){
		return PhotoQHelper::getArrayOfTermNames($postID, 'get_the_tags');
	}
	function getArrayOfCategoryNames($postID){
		return PhotoQHelper::getArrayOfTermNames($postID, 'get_the_category');
	}
	function getArrayOfTermNames($postID, $funcName = 'get_the_tags'){
		$terms = $funcName($postID);
		$result = array();
		if ( !empty( $terms ) ) {
			foreach ( $terms as $term )
			$result[] = $term->name;
		}
		return $result;
	}

	/**
	 * Returns matching content from a directory.
	 *
	 * @param string $path			path of the directory.
	 * @param string $matchRegex	regex a filename should match.
	 * @return array	path to files that matched
	 */
	function getMatchingDirContent($path, $matchRegex)
	{
		$path = rtrim($path, '/') . '/';
		$result = array();
		if ( $handle = opendir($path) ) {
			while (false !== ($file = readdir($handle))) {
				if (preg_match($matchRegex, $file)) { //only include files matching regex
					array_push($result, $path.$file);
				}
			}
			closedir($handle);
		}
		//sort alphabetically
		sort($result);
		return $result;
	}

	/**
	 * Generates automatic name for display from filename. Removes suffix,
	 * replaces underscores, dashes and dots by spaces and capitalizes only first
	 * letter of any word.
	 *
	 * @param string $filename
	 * @return string
	 */
	function niceDisplayNameFromFileName($filename){
		//remove suffix
		$displayName = preg_replace('/\.[^\.]*$/', '', $filename);
		//replace underscores and hyphens with spaces
		$replaceWithWhiteSpace = array('-', '_', '.');
		$displayName = str_replace($replaceWithWhiteSpace, ' ', $displayName);
		//proper capitalization
		$displayName = ucwords(strtolower($displayName));
		return $displayName;
	}



	/**
	 * Moves $oldfile to $newfile, overwriting $newfile if it exists. We have to use
	 * this function instead of the builtin PHP rename because the latter does not work as expected
	 * on Windows (cf comments @ http://ch2.php.net/rename). Returns TRUE on success, FALSE on failure.
	 *
	 * @param string $oldfile The path to the file to be moved
	 * @param string $newfile The path where $oldfile should be moved to.
	 *
	 * @return boolean TRUE if file is successfully moved
	 *
	 * @access public
	 */
	function moveFile(PhotoQ_File_SourceDestinationPair $srcDest)
	{
		$oldfile = $srcDest->getSource();
		$newfile = $srcDest->getDestination();
		if (!rename($oldfile,$newfile)) {
			if (copy($oldfile,$newfile)) {
				unlink($oldfile);
				return TRUE;
			}
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Same as above but returns error if file already exists at destination.
	 *
	 * @param string $oldfile The path to the file to be moved.
	 * @param string $newfile The path where $oldfile should be moved to.
	 * @return boolean TRUE if file successfully moved
	 */
	public function moveFileIfNotExists(PhotoQ_File_SourceDestinationPair $srcDest)
	{
		if(!$srcDest->destinationExists())
			return PhotoQHelper::moveFile($srcDest);
		else
			return FALSE;
	}

	function mergeDirs($oldfile, $newfile){
		if(!file_exists($newfile)){
			return PhotoQHelper::moveFile(new PhotoQ_File_SourceDestinationPair($oldfile,$newfile));
		}else
		if(is_dir($oldfile) && is_dir($newfile)){
			$oldfile = rtrim($oldfile,'/').'/';
			$newfile = rtrim($newfile,'/').'/';
			//get all visible files from old img dir
			$match = '#^[^\.]#';//exclude hidden files starting with .
			$visibleFiles = PhotoQHelper::getMatchingDirContent($oldfile, $match);
			foreach($visibleFiles as $file2merge){
				PhotoQHelper::mergeDirs($file2merge, str_replace($oldfile,$newfile,$file2merge));
			}
		}else{
			return false;
		}
	}

	/**
	 * Converts absolute path to relative url
	 *
	 * @param string $path
	 * @return string
	 */
	function getRelUrlFromPath($path)
	{
		//replace WP_CONTENT_DIR with WP_CONTENT_URL
		$wpcd = str_replace('\\', '/', WP_CONTENT_DIR);
		if(strpos($path, $wpcd) === 0)//it starts with WP_CONTENT_DIR
		return str_replace($wpcd, WP_CONTENT_URL, $path);

		//convert backslashes (windows) to slashes
		$abs = str_replace('\\', '/', ABSPATH);
		$path = str_replace('\\', '/', $path);
		//remove ABSPATH
		$relUrl = str_replace($abs, '', trim($path));
		//remove slashes from beginning
		//echo "<br/> relURl: $relUrl </br>";
		return trailingslashit( get_option( 'siteurl' ) ) . preg_replace('/^\/*/', '', $relUrl);
	}

	/**
	 * Reduces multidimensional array to single dimension.
	 *
	 * @param array $in
	 * @return array
	 */
	function flatten($in){
		$out = array();
		if(is_array($in)){
			foreach ($in as $key => $value){
				if(is_array($value)){
					unset($in[$key]);
					$out = array_merge($out,PhotoQHelper::flatten($value));
				}else
				$out[$key] = $value;
			}
		}
		return $out;
	}


	/**
	 * Gets an array of all the <$tag>content</$tag> tags contained in $string.
	 *
	 * @param string $tag
	 * @param string $string
	 * @return array
	 */
	function getHTMLTags($tag, $string)
	{
		$result = array();
		$bufferedOpen = array();
		$offset = 0;
		$nextClose = strpos($string, "</$tag>", $offset);
		while($nextClose !== false){
			$nextOpen = strpos($string, "<$tag", $offset);
			$offset = $nextClose;
			while($nextOpen < $nextClose && $nextOpen !== false){
				array_push($bufferedOpen,$nextOpen);
				$nextOpen = strpos($string, "<$tag", $nextOpen+1);
			}
			//we got a pair
			$start = array_pop($bufferedOpen);
			array_push($result,substr($string,$start,$nextClose-$start+strlen($tag)+3));
			$nextClose = strpos($string, "</$tag>", $nextClose+1);
		}
		return $result;
	}


	/**
	 * Fills in shorttags into the format string specified
	 * @param $format
	 * @param $keyValArray
	 * @return unknown_type
	 */
	function formatShorttags($format, $tagValArray)
	{
		foreach ($tagValArray as $tag => $val)
		$format = str_replace("[$tag]",$val,$format);
		return $format;
	}

	/**
	 * Determines whether the given shorttag is part of the formatting string given.
	 * @param $format
	 * @param $tag
	 * @return unknown_type
	 */
	function containsShorttag($format, $tag){
		return strpos($format, $tag) !== false;
	}

	/**
	 * Given array of shorttags, checks whether the format string contains least one of them.
	 * @param $format
	 * @param $tags
	 * @return unknown_type
	 */
	function containsAnyOfTheseShorttags($format, $tags){
		$format = stripslashes(html_entity_decode($format));
		foreach ($tags as $tag){
			if(PhotoQHelper::containsShorttag($format,$tag)){
				return true;
			}
		}
		return false;
	}


	/**
	 * Get the maximum allowable file size in KB from php.ini
	 *
	 * @return integer the maximum size in kilobytes
	 */
	function getMaxFileSizeFromPHPINI()
	{
		$max_upl_size = strtolower( ini_get( 'upload_max_filesize' ) );
		$max_upl_kbytes = 0;
		if (strpos($max_upl_size, 'k') !== false)
		$max_upl_kbytes = $max_upl_size;
		if (strpos($max_upl_size, 'm') !== false)
		$max_upl_kbytes = $max_upl_size * 1024;
		if (strpos($max_upl_size, 'g') !== false)
		$max_upl_kbytes = $max_upl_size * 1024 * 1024;

		return $max_upl_kbytes;
	}


	/**
	 * Logs message $msg to a file if debbugging is enabled.
	 *
	 * @param string $msg   The message to be logged to the file.
	 *
	 * @access public
	 */
	function debug($msg)
	{
		if(PHOTOQ_DEBUG_LEVEL >= PHOTOQ_LOG_MESSAGES){
			require_once realpath(PHOTOQ_PATH.'lib/Log-1.9.11/Log.php');
			$conf = array('mode' => 0777, 'timeFormat' => '%X %x');
			$logger = &Log::singleton('file', PHOTOQ_PATH.'log/out.log', '', $conf);
			$logger->log($msg);
		}
	}

	/**
	 * Escapes an entire array to prevent SQL injection.
	 * @param $array the array to be escaped.
	 * @return Array the escaped array
	 */
	function arrayAttributeEscape($array){
		if(is_array($array))
			return array_map("attribute_escape",$array);
		else
			return esc_attr($array);
	}

	/**
	 * Encodes all string elements of a (possibly nested) array using htmlentities.
	 * @param $array the array whose elements are to be encoded.
	 * @return Array the encoded array
	 */
	function arrayHtmlEntities($array){
		if(is_array($array))
		return array_map(array('PhotoQHelper', 'arrayHtmlEntities'),$array);
		else{
			if(is_string($array))
			return htmlentities($array);
			else
			return $array;
		}
	}
	

	/**
	 * Shows the list of meta fields
	 * @param $id int if given shows the meta field of queued photo with this id.
	 */
	function showMetaFieldList($postID = 0){
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		if($results = $fieldTable->getAllFields()){
			echo '<div class="info_group">';
				
			foreach ($results as $field_entry) {
				if($postID){
					//get posted values if any from common info
					$field_value = esc_attr(stripslashes($_POST[$field_entry->q_field_name][0]));
					if(empty($field_value)){
						//get the stored values
						$field_value = get_post_meta($postID, $field_entry->q_field_name, true);
					}
				}
				echo '<div class="info_unit">'.$field_entry->q_field_name.':<br /><textarea style="font-size:small;" name="'.$field_entry->q_field_name.'[]" cols="30" rows="3"  class="uploadform">'.$field_value.'</textarea></div>';
			}
				
			echo '</div>';
		}
	}
}