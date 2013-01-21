<?php

/**
 * This file generates the XML options that the user can download.
 * Based on WordPress Export Administration API
 *
 * @package PhotoQ
 */


//next are some access and nonce checks
if ( !is_user_logged_in() )
	die('-1');
	
check_admin_referer('photoqExportXML-nonce', 'photoqExportXML-nonce');

//Do we come from the form
if ( isset( $_POST['download'] ) ) {
	
	//create the filename
	if(!empty($_POST['xml-filename']))
		$filename = esc_attr($_POST['xml-filename']).'.xml';
	else
		$filename = 'my-theme-preset.' . date('Y-m-d') . '.xml';

	//send the proper headers
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=$filename");
	header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
	
	echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?' . ">\n";

?>

<!-- generator="PhotoQ/<?php echo PhotoQ::VERSION ?>" created="<?php echo date('Y-m-d H:i') ?>"-->

<photoQSave version="1.0">
<photoQSaveMeta>
	<generator>http://www.whoismanu.com/photoq-wordpress-photoblog-plugin/?v=<?php echo PhotoQ::VERSION ?></generator>
<?php if(!empty($_POST['xml-themename']) || !empty($_POST['xml-themeversion']) || !empty($_POST['xml-themecategory']) || !empty($_POST['xml-themeurl']) || !empty($_POST['xml-themeauthorname']) || !empty($_POST['xml-themeauthorcontact']) ):?>
	<theme>
<?php if(!empty($_POST['xml-themename'])):?>
		<name><?php echo esc_attr($_POST['xml-themename']) ?></name>
<?php endif; ?>
<?php if(!empty($_POST['xml-themeversion'])):?>
		<version><?php echo esc_attr($_POST['xml-themeversion']) ?></version>
<?php endif; ?>
<?php if(!empty($_POST['xml-themecategory'])):?>
		<category><?php echo esc_attr($_POST['xml-themecategory']) ?></category>
<?php endif; ?>
<?php if(!empty($_POST['xml-themeurl'])):?>
		<url><?php echo esc_attr($_POST['xml-themeurl']) ?></url>
<?php endif; ?>
<?php if( !empty($_POST['xml-themeauthorname']) || !empty($_POST['xml-themeauthorcontact']) ):?>
		<author>
<?php if( !empty($_POST['xml-themeauthorname']) ):?>
			<name><?php echo esc_attr($_POST['xml-themeauthorname']) ?></name>
<?php endif; ?>
<?php if( !empty($_POST['xml-themeauthorcontact']) ):?>
			<contact><?php echo esc_attr($_POST['xml-themeauthorcontact']) ?></contact>
<?php endif; ?>
		</author>
<?php endif; ?>
	</theme>
<?php endif; ?>
<?php if( !empty($_POST['xml-creatorname']) || !empty($_POST['xml-creatorcontact']) ):?>
	<creator>
<?php if( !empty($_POST['xml-creatorname']) ):?>
		<name><?php echo esc_attr($_POST['xml-creatorname']) ?></name>
<?php endif; ?>
<?php if( !empty($_POST['xml-creatorcontact']) ):?>
		<contact><?php echo esc_attr($_POST['xml-creatorcontact']) ?></contact>
<?php endif; ?>
	</creator>
<?php endif; ?>
</photoQSaveMeta>
<photoQSettings>
	<?php
		$oc = PhotoQ_Option_OptionController::getInstance();
		//add an entry for every meta field
		$fieldTable = new PhotoQ_DB_CustomFieldTable();
		$fieldNames = $fieldTable->getFieldNames();
		if(count($fieldNames)){
			print '<photoQFields>'. PHP_EOL;
			foreach($fieldNames as $fieldName)
				print '<field><name>'.$fieldName.'</name></field>'. PHP_EOL;
			print '</photoQFields>'. PHP_EOL;
		}
		if(isset($_POST['xml-defaultCats'])){
			print '<photoQDefaultCategories>'. PHP_EOL;
			print '<category><name>'.get_the_category_by_ID($oc->getValue('qPostDefaultCat')).'</name></category>'. PHP_EOL;
			print '</photoQDefaultCategories>'. PHP_EOL;
		}
		
		//what options do we include into this preset
		$includedOptions = array('imageSizes', 'views', 'exifDisplay');
		if(isset($_POST['xml-defaultTags']))
			array_push($includedOptions, 'qPostDefaultTags');
			
		//add an entry for every included option
		$oc->serizalize2xml($includedOptions); 
	?>
</photoQSettings>
</photoQSave>
<?php
}// end: if ( isset( $_POST['download'] ) ) {
?>