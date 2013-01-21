<?php
/**
 * This our own (stupid) little parser for options that were saved in XML format.
 * Have to make my own as we don't want to rely on any PHP extensions that may not
 * be present on some server plus the whole XML stuff changed btw PHP4 and PHP5.
 * @author manu
 *
 */
class PhotoQ_Option_XMLParser{

	/**
	 * The file to parse from
	 * @var String
	 */
	private $_xmlFile;
	
	/**
	 * The meta fields found in the xml file
	 * @var array
	 */
	private $_parsedFields = array();
	
	/**
	 * The default categories found in the xml file
	 * @var array
	 */
	private $_parsedCats = array();
	
	/**
	 * Array of options found in the xml file
	 * @var array
	 */
	private $_parsedOptions = array();
	
	private $_oc;
	private $_db;
	
	public function __construct($xmlFile){
		$this->_xmlFile = $xmlFile;
		$this->_db = PhotoQ_DB_DB::getInstance();
		$this->_oc = PhotoQ_Option_OptionController::getInstance();
	}
	
	private function _setupDefaultTags(){
		//add default tags to all photoq posts
		if(isset($this->_parsedOptions['qPostDefaultTags']['qPostDefaultTags']) && !empty($this->_parsedOptions['qPostDefaultTags']['qPostDefaultTags'])){
			$newTags = preg_split("/[\s]*,[\s]*/", $this->_parsedOptions['qPostDefaultTags']['qPostDefaultTags']);
			$postIDs = $this->_db->getAllPublishedPhotoIDs();
			foreach($postIDs as $id){
				//update the tags in the database
				wp_set_post_tags( $id, add_magic_quotes($newTags), true );
			}
			//update all posts in the queue
			$qEntries = $this->_getQueueIDTagPairs();
			foreach($qEntries as $entry){
				$tagString = array_unique(array_merge($newTags, $entry['tags']));
				wp_set_post_tags($entry['postID'], add_magic_quotes($tagString));
			}
		}
	}
	
	private function _getQueueIDTagPairs(){
		$qTable = new PhotoQ_DB_QueueTable();
		$ids = $qTable->getAllQueuedPhotoCustomPostIDs();
		$result = array();
		foreach($ids as $id){
			$result[] = array(
				'postID' => $id,
				'tags' => wp_get_post_tags($id, array('fields' => 'names'))
			);
		}
		return $result;
	}

	
	
	/**
	 * Imports options and fields from the XML file.
	 * @return boolean	true on success, false on failure
	 */
	public function importFromFile(){
		$this->_parse();
		
		if(!$this->_validate())
			throw new PhotoQ_Error_Exception(
				sprintf(__('The XML file "%s" could not be imported', 'PhotoQ'), $this->_xmlFile));

		$this->_setupImageSizes();
		$this->_setupViews();
		$this->_setupFields();
		$this->_setupDefaultTags();
		$this->_setupDefaultCategory();
		$this->_setupOptions();
	}

	/**
	 * This one is based on the WordPress import file wordpress.php
	 * @return unknown_type
	 */
	private function _parse(){
		//let's open the file for parsing
		$fp = fopen($this->_xmlFile, 'r');
		if ($fp) {
			
			//Two stacks, one for the options, one for their name. Needed while we 
			//traverse the option tree.
			$optionStack = array();
			$optionNameStack = array();
			
			//The current option and its name that is being processed
			$currentArray = array();
			$currentName = 'allOptions';
			
			//flags in case we need some state information about what is being parsed
			$doing_fields = false; //are we currently processing meta fields?
			$doing_cats = false; //...or default categories?
			$doing_options = false; //...or rather PhotoQ options?
			$doing_value = false; //...or is it maybe a multiline value?
			
			//keys and values of value array that were already processed
			$currentKey = '';
			$currentVal = '';
			//the currentValue, needed in case we have multiline values
			$currentValue = '';
			
			//parse the file line-by-line
			while ( !feof($fp) ) {
				$importline = rtrim(fgets($fp));
				
				if ( false !== strpos($importline, '<photoQFields>') ) {
					$doing_fields = true;
					continue;
				}
				if ( false !== strpos($importline, '</photoQFields>') ) {
					$doing_fields = false;
					continue;
				}
				if ( $doing_fields  ) {
					preg_match('#<field><name>(.*?)</name></field>#', $importline, $fieldname);
					$this->_parsedFields[] = esc_attr($fieldname[1]);
				}
				
				if ( false !== strpos($importline, '<photoQDefaultCategories>') ) {
					$doing_cats = true;
					continue;
				}
				if ( false !== strpos($importline, '</photoQDefaultCategories>') ) {
					$doing_cats = false;
					continue;
				}
				if ( $doing_cats  ) {
					preg_match('#<category><name>(.*?)</name></category>#', $importline, $catname);
					$this->_parsedCats[] = esc_attr($catname[1]);
				}

				if ( false !== strpos($importline, '<photoQOptions>') ) {
					$doing_options = true;
					continue;
				}
				if ( false !== strpos($importline, '</photoQOptions>') ) {
					$doing_options = false;
					continue;
				}
				if ($doing_options){
					if ( false !== strpos($importline, '<option ') ) {
						preg_match('#<option name="(.*?)".*>#', $importline, $optname);
						array_push($optionNameStack, $currentName);
						array_push($optionStack, $currentArray);
						$currentName = $optname[1];
						$currentArray = array();
						continue;
					}
					if ( false !== strpos($importline, '<arrayValue>') ) {
						array_push($optionNameStack, $currentName);
						array_push($optionStack, $currentArray);
						//$currentName = $optname[1];
						$currentArray = array();
						continue;
					}
					if ( false !== strpos($importline, '<entry>') ) {
						$currentKey = '';
						$currentVal = '';
						continue;
					}
					if ( false !== strpos($importline, '</entry>') ) {
						$currentArray[$currentKey] = $currentVal;
						continue;
					}
					if ( false !== strpos($importline, '<key>') ) {
						preg_match('#<key>(.*?)</key>#', $importline, $optval);
						$currentKey = $optval[1];
						continue;
					}
					if ( false !== strpos($importline, '<val>') ) {
						preg_match('#<val>(.*?)</val>#', $importline, $optval);
						$currentVal = $optval[1];
						continue;
					}
					if ( false !== strpos($importline, '<value>') && false !== strpos($importline, '</value>') ) {
						preg_match('#<value>(.*?)</value>#', $importline, $optval);
						$currentArray[$currentName] = $this->_unhtmlentities(str_replace(array ('<![CDATA[', ']]>'), '', $optval[1]));
						continue;
					}
					if ( false !== strpos($importline, '<value>') ) {
						$doing_value = true;
						preg_match('#<value>(.*)#', $importline, $optval);
						$currentValue = $this->_unhtmlentities(str_replace(array ('<![CDATA[', ']]>'), '', $optval[1]));
						continue;
					}
					if($doing_value){//multi-line value
						if ( false !== strpos($importline, '</value>') ) {
							$doing_value = false;
							preg_match('#(.*?)</value>#', $importline, $optval);
							$currentValue .= $this->_unhtmlentities(str_replace(array ('<![CDATA[', ']]>'), '', $optval[1]));
							$currentArray[$currentName] = $currentValue;
							continue;
						}else{
							$currentValue .= PHP_EOL . $this->_unhtmlentities(str_replace(array ('<![CDATA[', ']]>'), '', $importline)) . PHP_EOL;
							continue;
						}
					}
					if ( false !== strpos($importline, '</option>') ) {
						$oldName = $currentName;
						$oldArray = $currentArray;
						$currentName = array_pop($optionNameStack);
						$currentArray = array_pop($optionStack);
						$currentArray[$oldName] = $oldArray;
						continue;
					}
					if ( false !== strpos($importline, '</arrayValue>') ) {
						$oldArray = $currentArray;
						$currentName = array_pop($optionNameStack);
						$currentArray = array_pop($optionStack);
						$currentArray[$currentName] = $oldArray;
						continue;
					}
				}


			}

			fclose($fp);
		}
		
		//$currentArray now holds the whole option array
		$this->_parsedOptions = $currentArray;
	}
	
	/**
	 * Performs some sanitiy checks on the data parsed from the XML file.
	 * @return unknown_type
	 */
	private function _validate(){
		//check that only allowed options are being imported
		$allowed = array('imageSizes', 'views', 'exifDisplay', 'qPostDefaultCat', 'qPostDefaultTags');
		foreach(array_keys($this->_parsedOptions) as $optionName)
			if(!in_array($optionName,$allowed)){
				add_settings_error('wimpq-photoq', 'xml-option-denied',
					sprintf(__('The option %s that you tried to import is not allowed.', 'PhotoQ'), $optionName), 'error');
				return false;
			}
				
		
		//check that views and image sizes contain the standard fixed elements.
		if(!$this->_expCompContainsRequiredFixedElements('views', array('content', 'excerpt'))){
			return false;
		}if(!$this->_expCompContainsRequiredFixedElements('imageSizes', array('main', 'thumbnail'))){
			return false;
		}
		//do not add a view if a meta field with same name exists already or is also requested
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fieldNames = array_unique(array_merge($this->_parsedFields, $fieldTable->getFieldNames()));
		$conflictingNames = array_intersect($fieldNames, $this->_getViewNames());
		if(!empty($conflictingNames)){
			foreach($conflictingNames  as $conflictingName){
				add_settings_error(
					'wimpq-photoq', 'field-exists',
					sprintf(__('Please choose another name, a meta field with name "%s" already exists.', 'PhotoQ'), $conflictingName), 
					'error'
				);	
			}
			return false;
		}
		return true;
		
	}
	
	private function _setupImageSizes(){
		//delete image sizes that are no longer used in the new settings
		$this->_deleteObsoleteImageSizes();
	}

	private function _setupViews(){
		//delete views that are no longer used in the new settings
		$this->_deleteObsoleteViews();
			
		//add custom fields required by views setting of xml file,
		//conflicts with fields will not happen as this is already tested in validation.
		foreach ($this->_getViewNames(true) as $view){
			$this->_oc->addViewCallback($view, true);
		}

	}

	private function _setupFields(){
		//create fields required by the xml file
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		foreach($this->_parsedFields as $fieldName){
			if(!$fieldTable->exists($fieldName) && $fieldTable->insertField($fieldName))
				$this->_db->addInitialFieldMeta($fieldName);
		}
	}
	
	private function _setupDefaultCategory(){
		if($defaultCat = $this->_getDefaultCategory()){
			if(!is_wp_error($catID = $this->_getCategoryID($defaultCat))){
				$this->_db->addCategoryToAllPhotoQPosts($catID);	
			}
		}
	}
	
	private function _getCategoryID($catName){
		if(!category_exists($catName)){
			$catID = wp_insert_category(array('cat_name' => $catName));
		}else{
			$catID = get_cat_id($catName);
		}
		return $catID;
	}
		
	private function _setupOptions(){
		$this->_oc->forceOptions($this->_parsedOptions);
	}

	private function _unhtmlentities($string) { // From php.net for < 4.3 compat
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}
	
	private function _expCompContainsRequiredFixedElements($compositeName, $requiredElements){
		if(isset($this->_parsedOptions[$compositeName][$compositeName])){
			if(is_array($this->_parsedOptions[$compositeName][$compositeName])){
				//it has to contain every required element plus they cannot be removeable
				foreach ($requiredElements as $element){
					if(!$this->_containsFixedElement($compositeName,$element))
						return false;
				}
			}else
				return false;
		}
		return true;
	}
	
	private function _containsFixedElement($compositeName,$element){
		$result = true;
		if(isset($this->_parsedOptions[$compositeName][$compositeName][$element])){
			if($this->_parsedOptions[$compositeName][$compositeName][$element])
				$result = false;
		}else{
			add_settings_error('wimpq-photoq', 'xml-element-missing',
					sprintf(__('XML import error: Element %1$s was missing in %2$s option.', 'PhotoQ'), $element, $compositeName), 'error');			
			$result = false;
		}
		return $result;
	}

	
	private function _getViewNames($onlyRemoveable = false){
		return $this->_getElementNames('views', $onlyRemoveable);
	}
	
	private function _getImageSizeNames($onlyRemoveable = false){
		return $this->_getElementNames('imageSizes', $onlyRemoveable);
	}
	
	private function _getElementNames($expComp, $onlyRemoveable = false){
		$result = array();
		if(isset($this->_parsedOptions[$expComp][$expComp])){
			if(is_array($this->_parsedOptions[$expComp][$expComp])){
				foreach ($this->_parsedOptions[$expComp][$expComp] as $elem => $removeable){
					if(!$onlyRemoveable || $removeable){
						$result[] = $elem;
					}
				}
			}
		}
		return $result;
	}
	
	private function _getDefaultCategory(){
		//right now we only support one default category
		if(!empty($this->_parsedCats))
			return $this->_parsedCats[0];
		return null;
	}

	/**
	 * Delete custom fields associated to views that are no longer present in 
	 * the newly imported XML view settings
	 * @param $currentViews	array of current views
	 * @param $allParsedViews	array of views in the imported XML settings
	 * @return unknown_type
	 */
	private function _deleteObsoleteViews(){
		$this->_deleteObsoleteElements(
			'views', array($this->_oc, 'delViewCallback'), $this->_oc->getViewNames(), $this->_getViewNames()); 
	}
	
	private function _deleteObsoleteImageSizes(){
		$this->_deleteObsoleteElements(
			'imageSizes', array($this->_oc, 'delImageSizeCallback'), $this->_oc->getImageSizeNames(), $this->_getImageSizeNames()); 
	}
	
	
	private function _deleteObsoleteElements($compName, $compCallback, $currentElements, $allParsedElements){
		if(!empty($allParsedElements)){
			$obsoleteElements = array_diff($currentElements, $allParsedElements);
			foreach($obsoleteElements as $elementName){
				call_user_func_array($compCallback, array($elementName));
				//also remove it from the options, otherwise it will still show
				//up until the page is refreshed.
				$this->_oc->deleteElementFromComposite($compName, $elementName);
			}
		}
	}
				
	

}