<?php
/**
 * @package ReusableOptions
 */
 

/**
 * The RO_Visitor_UpdateOptionVisitor:: is responsible for updating visited options. It 
 * typically visits objects after form submission.
 *
 * @author  M. Flury
 * @package ReusableOptions
 */
class RO_Visitor_UpdateOptionVisitor extends RO_Visitor_OptionVisitor
{
	
	/**
	 * Abstract implementation of the visitTextField() method called whenever a
	 * RO_Option_TextField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_TextField $textField	Reference to visited option.
	 */
	 function visitRO_Option_TextFieldBefore($textField)
	 {
	 	if(isset($_POST[$textField->getPOSTName()]))
	 		$textField->setValue(esc_attr($_POST[$textField->getPOSTName()]));
	 }
	 
	 
	 function visitRO_Option_StrictValidationTextFieldBefore($textField)
	 {
	 	$oldValue = $textField->getValue();
	 	$this->visitRO_Option_TextFieldBefore($textField);
	 	//check whether we pass validation if not put back the old value
	 	if(!$textField->validate())
	 		$textField->setValue($oldValue);	
	 }
	
	/**
	 * Abstract implementation of the visitTextField() method called whenever a
	 * RO_Option_TextField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_TextField $textField	Reference to visited option.
	 */
	 function visitRO_Option_PasswordTextFieldBefore($textField)
	 {
	 	$this->visitRO_Option_TextFieldBefore($textField);
	 }
	 
	 /**
	  * Abstract implementation of the visitTextField() method called whenever a
	  * RO_Option_TextFieldSiteOption is visited. Contrary to standard text field, for WPMU
	  * we only allow site_admins to make changes.
	  *
	  * @param object RO_Option_TextField $textField	Reference to visited option.
	  */
	 function visitRO_Option_TextFieldSiteOptionBefore($textField)
	 {
	 	if(!is_multisite() || is_super_admin())
			$this->visitRO_Option_TextFieldBefore($textField);
	 }

	 
	  
	 /**
	 * Abstract implementation of the visitTextArea() method called whenever a
	 * RO_Option_TextArea is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_TextArea $textArea	Reference to visited option.
	 */
	 function visitRO_Option_TextAreaBefore($textArea)
	 {
	 	if(isset($_POST[$textArea->getPOSTName()]))
	 		$textArea->setValue(esc_attr($_POST[$textArea->getPOSTName()]));
	 		//$textArea->setValue(str_replace(array("\r\n", "\r", "\n"),PHP_EOL,esc_attr($_POST[$textArea->getPOSTName()])));
	 }
	 
	 /**
	 * Abstract implementation of the visitHiddenInputField() method called whenever a
	 * HiddenInputField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object HiddenInputField $hiddenInputField	Reference to visited option.
	 */
	 function visitRO_Option_HiddenInputFieldBefore($hiddenInputField)
	 {
	 	if(isset($_POST[$hiddenInputField->getPOSTName()]))
	 		$hiddenInputField->setValue(esc_attr($_POST[$hiddenInputField->getPOSTName()]));
	 }
	
	/**
	 * Abstract implementation of the visitCheckBox() method called whenever a
	 * RO_Option_CheckBox is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_CheckBox $checkBox	Reference to visited option.
	 */
	 function visitRO_Option_CheckBoxBefore($checkBox)
	 {
	 	if (!isset($_GET['action']))
	 		$checkBox->setValue(isset($_POST[$checkBox->getPOSTName()]) ? '1' : '0');
	 }

	/**
	 * Abstract implementation of the visitCheckBox() method called whenever a
	 * RO_Option_CheckBox is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_CheckBox $checkBox	Reference to visited option.
	 */
	 function visitRO_Option_RadioButtonListBefore($radioButtonList)
	 {	
	 	if(isset($_POST[$radioButtonList->getPOSTName()]))
	 		$radioButtonList->setValue($_POST[$radioButtonList->getPOSTName()]);
	 }
	 
	 function visitRO_Option_CheckBoxListBefore($checkBoxList)
	 {	
	 	if (!isset($_GET['action']))
	 		$checkBoxList->setValue(isset($_POST[$checkBoxList->getPOSTName()]) ? $_POST[$checkBoxList->getPOSTName()] : NULL);
	 }
	 
	 
	 /**
	 * Abstract implementation of the visitRO_Option_DropDownListBefore() method called whenever a
	 * RO_Option_CheckBox is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_DropDownList $dropDownList	Reference to visited option.
	 */
	 function visitRO_Option_DropDownListBefore($dropDownList)
	 {	
	 	if(isset($_POST[$dropDownList->getPOSTName()]))
	 		$dropDownList->setValue($_POST[$dropDownList->getPOSTName()]);
	 }
	 

	 function visitRO_Option_ReorderableListBefore($reorderableList){
	 	if(isset($_POST[$reorderableList->getFieldName()]))
	 		$reorderableList->setValue($_POST[$reorderableList->getFieldName()]);
	 }

	 //check whether a new option is being added
	 function visitRO_Option_ExpandableCompositeBefore($option){
	 	if (isset($_POST['addExpComp-'.$option->getName()])) {
	 		//name has to be save to create directories and not empty.
	 		$name = preg_replace('/[^a-zA-Z0-9_\-]/','_',$_POST['newExpComp-'.$option->getName()]);
	 		if(!empty($name)){
	 			$addOk = true;
	 			//callback to be executed if we add a child
				if(method_exists($option->_onAddCallback[0], $option->_onAddCallback[1]))
					$addOk = call_user_func_array(array($option->_onAddCallback[0], $option->_onAddCallback[1]), array($name));
				
				//only add if the onAddCallback returned true
				if($addOk){
	 				$className = $option->_childClassName;
	 				$option->addChild(new $className($name),1);
				}	 		
	 		}else
	 			echo "ERROR: invalid name";
	 	}
	 	if (isset($_GET['action']) && $_GET['action'] == 'delExpComp-'.$option->getName()) {
	 		$name = esc_attr($_GET['entry']);
	 		//check for correct nonce first
	 		check_admin_referer('delExpComp'.$name. '-nonce');
	 		$delOk = true;
	 		//callback to be executed if we remove a child
	 		if(method_exists($option->_onDelCallback[0], $option->_onDelCallback[1]))
	 			$delOk = call_user_func_array(array($option->_onDelCallback[0], $option->_onDelCallback[1]), array($name));

			//only delete if the onDelCallback returned true
	 		if($delOk){
	 			$option->removeChild($name);
	 		}
	 	}
	 }

	 
	 
	 /**
	  * Method called whenever any option is visited.
	  *
	  * @param object RO_Option_ReusableOption $option	Reference to visited option.
	  */
	 function visitDefaultBefore($option)
	 {
	 	$option->storeOldValues();
	 }

	 /**
	  * Method called whenever any option is visited.
	  *
	  * @param object RO_Option_ReusableOption $option	Reference to visited option.
	  */
	 function visitDefaultAfter($option)
	 {
	 	$option->updateChangedStatus();
	 }


}

