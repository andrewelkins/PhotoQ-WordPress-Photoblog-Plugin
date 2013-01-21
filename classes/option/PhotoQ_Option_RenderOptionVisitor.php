<?php
/**
 * The PhotoQ_Option_RenderOptionVisitor:: is responsible for rendering of the options. It 
 * renders every visited option in HTML.
 *
 * @author  M. Flury
 * @package PhotoQ
 */
class PhotoQ_Option_RenderOptionVisitor extends RO_Visitor_RenderOptionVisitor
{
	
	
	 
	/**
	 * Method called whenever a
	 * PhotoQ_Option_ImageSizeOption is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object PhotoQ_Option_ImageSizeOption $dropDownList	Reference to visited option.
	 */
	 function visitPhotoQ_Option_ImageSizeOptionBefore($imageSize)
	 {
	 	
	 	print '<table width="100%" cellspacing="2" cellpadding="5" class="form-table noborder">
	 				<tr valign="top">
	 					<th class="imageSizeName"> ' .$imageSize->getName().'</th>
	 					<td></td>
	 				</tr>';
	 	
	 }
	 
	 /**
	 * Method called whenever a
	 * PhotoQ_Option_ImageSizeOption is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object PhotoQ_Option_ImageSizeOption $imageSize	Reference to visited option.
	 */
	 function visitPhotoQ_Option_ImageSizeOptionAfter($imageSize)
	 {
	 	print "</table>";
	 }
	 
	 
	 public function visitPhotoQ_Option_ExifTagOptionBefore($option)
	 {
	 	print '<b>'.$option->getExifKey().'</b> ( '.$option->getExifExampleValue().' )<br/>'.PHP_EOL;
	 }
	 
	 public function visitPhotoQ_Option_RoleOptionBefore($option){
	 	print $option->getTextBefore();
	 	print $option->getLabel().':'.PHP_EOL;
	 	print '<ul>'.PHP_EOL;	 
	 }

	 public function visitPhotoQ_Option_RoleOptionAfter($option){
	 	print '</ul>'.PHP_EOL;
	 	print $option->getTextAfter();
	 }
	 
	public function visitPhotoQ_Option_TaxonomyCheckBoxListBefore($option){
		print $option->getTextBefore();
	 	print '<ul>'.PHP_EOL;
	 }

	 public function visitPhotoQ_Option_TaxonomyCheckBoxListAfter($option){
	 	print '</ul>'.PHP_EOL;
	 	print $option->getTextAfter();
	 }
	 
	 
	 public function visitPhotoQ_Option_ViewOptionBefore($imageSize)
	 {
	 	print '<table width="100%" cellspacing="2" cellpadding="5" class="form-table noborder">
	 				<tr valign="top">
	 					<th class="viewName"> ' .$imageSize->getName().'</th>
	 					<td></td>
	 				</tr>';
	 	
	 }
	 
	 function visitPhotoQ_Option_ViewOptionAfter($imageSize)
	 {
	 	print "</table>";
	 }
	 
	 	
}