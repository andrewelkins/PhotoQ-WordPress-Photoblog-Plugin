<?php

/**
 * PhotoQClassLoader takes care of inclusion and (lazy) loading of all
 * class files that are required by PhotoQ.
 * @author manu
 *
 */
class PhotoQClassLoader
{
	/**
	 * The convention that should be used when naming classes.
	 * The naming convention tells the classloader where to load from.
	 * @var PhotoQClassNamingConvention
	 */
	private $_namingConvention;
	
	/**
	 * Constructor registers the autoload callback functions.
	 */
	public function __construct(PhotoQClassNamingConvention $className){
		$this->_namingConvention = $className;
		$this->_registerAutoloadCallbacks();
	}
	
	/**
	 * Loads the required PhotoQ class.
	 * @param string $class
	 */
	public function autoload($class)
	{
		if(!$this->_namingConvention->isValid($class)){
			return;
		}
			
		$file2Include = $this->_namingConvention->getPath($class);
		
		if(file_exists($file2Include))
			require_once($file2Include);
			
	}

	/**
	 * Needed in case another plugin defined the __autoload function.
	 * Just acts as a proxy to the __autoload function.
	 * @link http://ditio.net/2009/05/17/php-plugin-autoload/
	 * @param string $class
	 */	
	public function autoloadProxy($class)
	{
		if(function_exists("__autoload")) {
			__autoload($class);
		}
	}
	
	/**
	 * Registers the autolaod callbacks with PHP.
	 */
	private function _registerAutoloadCallbacks(){
		spl_autoload_register(array($this, 'autoload'));
		spl_autoload_register(array($this, 'autoloadProxy'));
	}
	
	
}

/**
 * Defines the Naming Convention that is used when autoloading PhotoQ classes.
 * @author manu
 *
 */
class PhotoQClassNamingConvention
{
	/**
	 * Prefixed to the part of path defined by classname
	 * @var unknown_type
	 */
	private $_pathPrefix;
	
	/**
	 * Prefix without trailing underscores. Identifies PhotoQ classes.
	 * @var unknown_type
	 */
	private $_identifier;
	
	/**
	 * Length of prefix.
	 * @var integer
	 */
	private $_identifierLen;
	
	/**
	 * Search for this in classname and replace with $_replace
	 * @var array
	 */
	private $_search;
	
	/**
	 * Replace $_search in classname with this.
	 * @var array
	 */
	private $_replace;
	
	/**
	 * 
	 * @param string $pathPrefix
	 * @param string $prefix
	 * @param string $delimiter
	 */
	function __construct($pathPrefix, $prefix, $delimiter){
		
		$this->_pathPrefix = $pathPrefix;
		$this->_identifier = rtrim($prefix,'_');
		$this->_identifierLen = strlen($this->_identifier);
		$this->_search = array($prefix, $delimiter);
		$this->_replace = array('', '/');
		
	}
	
	/**
	 * Constructs the path to include from from the name of the class.
	 * @param string $class
	 */
	function getPath($class){
		return $this->_pathPrefix . $this->_getDirname($class) . $class . '.php';
	}
	
	/**
	 * Checks whether a class name corresponds to the naming convention.
	 * Returns true if the identifier in the classname matches the identifier of
	 * the naming convention. An identifier is the part of the prefix up to and
	 * not including the last underscore.
	 * @param string $class
	 * @return boolean
	 */
	function isValid($class){
		return substr($class, 0,$this->_identifierLen) === $this->_identifier;
	}
	
	/**
	 * Gets the name of the directory from which to include $class from.
	 * Removes prefix, replaces underscores with slashes, sets all intermediate dirs to
	 * lowercase and discards the filename part of the path.
	 * @param string $class class that we want to include
	 * @return string
	 */
	private function _getDirname($class){
		$dirname = strtolower(dirname(str_replace($this->_search, $this->_replace, $class))).'/';
		if($dirname === './')
			$dirname = '';
		return $dirname;
	}

}

//set up the class loader and register the callbacks by creating an instance
$photoQLoader = new PhotoQClassLoader(new PhotoQClassNamingConvention(PHOTOQ_PATH.'classes/', 'PhotoQ_', '_'));

function loadExternalPhotoQLibraries(){
		//Load PEAR_ErrorStack which is used for error handling.
		//careful if some other plugin already required ErrorStack (but from
		//a different path we are not allowed to redefine
		/*if (!class_exists("ErrorStack"))
			require_once(PHOTOQ_PATH.'lib/PEAR_ErrorStack/ErrorStack.php');

		//temporary error handling through stack -> replace with exceptions
		require_once(PHOTOQ_PATH.'classes/error/PhotoQTempError.php');
		*/
		
		$reusableOptionsLoader = new PhotoQClassLoader(new PhotoQClassNamingConvention(PHOTOQ_PATH.'lib/ReusableOptions/classes/', 'RO_', '_'));
		
		// import ReusableOptions Library, same here add safety check
		//if (!class_exists("OptionController"))
		//	require_once(PHOTOQ_PATH.'lib/ReusableOptions/OptionController.php');
		
		// load the savant3 template engine
		//if (!class_exists("Savant3"))
		//	require_once(PHOTOQ_PATH.'lib/Savant3-3.0.1/Savant3.php');
}
loadExternalPhotoQLibraries();
