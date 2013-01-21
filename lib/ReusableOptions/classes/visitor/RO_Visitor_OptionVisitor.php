<?php
/**
 * @package ReusableOptions
 */
 

/**
 * The RO_Visitor_OptionVisitor:: is the abstract parent class of a Visitor pattern allowing
 * to perform operations on a hierarchy of options. 
 *
 * @author  M. Flury
 * @package ReusableOptions
 */
class RO_Visitor_OptionVisitor
{
	
	/**
	 * Abstract implementation of the visitTextField() method called whenever a
	 * RO_Option_TextField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_TextField $textField	Reference to visited option.
	 */
	 function visitTextField($textField)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitPasswordTextField() method called whenever a
	 * RO_Option_PasswordTextField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_PasswordTextField $textField	Reference to visited option.
	 */
	 function visitPasswordTextField($textField)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitTextArea() method called whenever a
	 * RO_Option_TextArea is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_TextArea $textArea	Reference to visited option.
	 */
	 function visitTextArea($textArea)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitHiddenInputField() method called whenever a
	 * HiddenInputField is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object HiddenInputField $hiddenInputField	Reference to visited option.
	 */
	 function visitHiddenInputField($hiddenInputField)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitCheckBox() method called whenever a
	 * RO_Option_CheckBox is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_CheckBox $checkBox	Reference to visited option.
	 */
	 function visitCheckBox($checkBox)
	 {
	 	return false;
	 }
	 
	/**
	 * Abstract implementation of the visitCheckBoxList() method called whenever a
	 * RO_Option_CheckBoxList is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_CheckBoxList $checkBoxList	Reference to visited option.
	 */
	 function visitCheckBoxList($checkBoxList)
	 {
	 	return false;
	 }
	 
	/**
	 * Abstract implementation of the visitCheckBoxListOption() method called whenever a
	 * RO_Option_CheckBoxListItem is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_CheckBoxListItem $checkBox	Reference to visited option.
	 */
	 function visitCheckBoxListOption($checkBox)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitRO_Option_RadioButtonList() method called whenever a
	 * RO_Option_RadioButtonList is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_RadioButtonList $radioButtonList	Reference to visited option.
	 */
	 function visitRO_Option_RadioButtonListBefore($radioButtonList)
	 {
	 	return false;
	 }
	 
	/**
	 * Abstract implementation of the visitRO_Option_RadioButtonList() method called whenever a
	 * RO_Option_RadioButtonList is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_RadioButtonList $radioButtonList	Reference to visited option.
	 */
	 function visitRO_Option_RadioButtonListAfter($radioButtonList)
	 {
	 	return false;
	 }
	 
	 
	 /**
	 * Abstract implementation of the visitRadioButton() method called whenever a
	 * RO_Option_RadioButton is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_RadioButton $radioButton	Reference to visited option.
	 */
	 function visitRadioButton($radioButton)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitRO_Option_DropDownListBefore() method called whenever a
	 * RO_Option_DropDownList is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_DropDownList $dropDownList	Reference to visited option.
	 */
	 function visitRO_Option_DropDownListBefore($dropDownList)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitRO_Option_DropDownListAfter() method called whenever a
	 * RO_Option_DropDownList is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_DropDownList $dropDownList	Reference to visited option.
	 */
	 function visitRO_Option_DropDownListAfter($dropDownList)
	 {
	 	return false;
	 }
	 
	 /**
	 * Abstract implementation of the visitRO_Option_DropDownItem() method called whenever a
	 * RO_Option_DropDownItem is visited. Subclasses should override this and and
	 * define the operation to be performed.
	 *
	 * @param object RO_Option_DropDownItem $dropDownOption	Reference to visited option.
	 */
	 function visitRO_Option_DropDownItem($dropDownOption)
	 {
	 	return false;
	 }
	 
	 

}

