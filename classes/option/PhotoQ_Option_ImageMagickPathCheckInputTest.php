<?php
/**
 * The PhotoQ_Option_ImageMagickPathCheckInputTest:: checks whether 
 * imagemagick path really leads to imagemagick.
 *
 * @author  M.Flury
 * @package PhotoQ
 */
class PhotoQ_Option_ImageMagickPathCheckInputTest extends RO_Validation_InputTest
{
	
	/**
	 * Concrete implementation of the validate() method. This methods determines 
	 * whether input validation passes or not.
	 * @param object RO_Option_ReusableOption $target 	The option to validate.
	 * @return String 	The error message created by this test.
	 * @access public
	 */
	function validate($target)
	{	
		require_once(PHOTOQ_PATH.'lib/phpThumb_1.7.9x/phpthumb.class.php');
		// create phpThumb object
		$phpThumb = new phpThumb();
		$phpThumb->config_imagemagick_path = ( $target->getValue() ? $target->getValue() : null );
		//under windows the version check doesn't seem to work so we also check for availability of resize
		if ( !$phpThumb->ImageMagickVersion() && !$phpThumb->ImageMagickSwitchAvailable('resize') ) {
    		$errMsg = __("Note: ImageMagick does not seem to be installed at the location you specified. ImageMagick is optional but might be needed to process bigger photos, plus PhotoQ might run faster if you configure ImageMagick correctly. If you don't care about ImageMagick and are happy with using the GD library you can safely ignore this message.",'PhotoQ');
    		$this->raiseErrorMessage($errMsg);
			return false;
		}
		return true;
	}
	
	
}

