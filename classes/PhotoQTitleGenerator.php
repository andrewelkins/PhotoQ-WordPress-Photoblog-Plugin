<?php
/**
 * Helper class that is able to generate auto titles from filenames.
 * @author manu
 *
 */
class PhotoQTitleGenerator
{
	
	private $_customRegexFilter;
	private $_noCapsList;
	private $_noCapsMaxWordLen;
	private $_capsList;
	
	/**
	 * Constructor of a PhotoQTitleGenerator
	 * @param string $customRegexFilter regular expression indicating what to remove from filename	
	 * @param string $noCapsList comma separated list of words that never should be capitalized
	 * @param integer $noCapsMaxWordLen maximum number of letters for words that are not captialized
	 * @param string $capsList comma separated list of words that are always capitalized
	 */
	public function __construct($customRegexFilter, $noCapsList, $noCapsMaxWordLen, $capsList){
		$this->_customRegexFilter = $customRegexFilter;
		$this->_noCapsList = $noCapsList;
		$this->_noCapsMaxWordLen = $noCapsMaxWordLen;
		$this->_capsList = $capsList;
	}
	
	/**
	 * Generates automatic title from filename. Removes suffix,
	 * replaces underscores by spaces and capitalizes only first
	 * letter of any word.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function generateAutoTitleFromFilename($filename){
		
		$title = $this->_applyUserFilter($filename);
		$title = $this->_removeSuffix($title);
		$title = $this->_replaceWithWhiteSpace(array('-', '_'), $title);
		$title = $this->_removeExcessWhitespace($title);
		$title = trim($title);
		
		$title = ucwords(strtolower($title));
		$title = $this->_userDefinedToLower($title);
		$title = $this->_shortToLower($title);
		$title = $this->_userDefinedExceptionsToUpper($title);
		
		return addslashes($title);
	}
	
	
	private function _applyUserFilter($title){
		return preg_replace('/'.stripslashes($this->_customRegexFilter).'/', '', $title);
	}
	
	private function _removeSuffix($title){
		return preg_replace('/\..*?$/', '', $title);
	}
	
	private function _replaceWithWhiteSpace(array $replaceUs, $title){ 
		return str_replace($replaceUs, ' ', $title);
	}
	
	/**
	 * Uncapitalizes user defined words except first and last word
	 * @param string $title
	 * @return string
	 */
	private function _userDefinedToLower($title){
		$noCaps = explode(',', str_replace(' ', '', $this->_noCapsList));
		foreach($noCaps as $toLower){
			$title = str_ireplace(' '.$toLower.' ', strtolower(' '.$toLower.' '), $title);
		}
		return $title;
	}
	
	/**
	 * Uncapitalize short words, first and last are always capital
	 * @param string $title
	 * @return string
	 */
	private function _shortToLower($title){
		$words = explode(' ', $title);
		$titleLen = count($words);
		for($i = 1; $i < $titleLen-1; $i++){
			if(strlen($words[$i]) <= $this->_noCapsMaxWordLen)
				$words[$i] = strtolower($words[$i]);
		}
		return implode(' ', $words);
	}
	
	/**
	 * recapitalize user defined excepted words
	 * @param string $title
	 * @return string
	 */
	private function _userDefinedExceptionsToUpper($title){
		$caps = explode(',', str_replace(' ', '', $this->_capsList));
		foreach($caps as $toUpper){
			$title = str_ireplace(' '.$toUpper.' ', strtoupper(' '.$toUpper.' '), $title);
		}
		return $title;
	}
	
	private function _removeExcessWhitespace($title){
		return preg_replace('/\s\s+/', ' ', $title);
	}

}