<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form;

defined('_JEXEC') or die;

/**
 * Generic interface that a FOF form field class must implement
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
interface FieldInterface
{
	/**
	 * Method to attach a JForm object to the field. Actually, we need a FOF Form object but there's no way to provide
	 * that type hint without issuing a string standards notice in PHP :(
	 *
	 * @param   Form  $form  The JForm object to attach to the form field.
	 *
	 * @return  FieldInterface  The form field object so that the method can be used in a chain.
	 */
	public function setForm(\JForm $form);

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null);

	/**
	 * Simple method to set the value
	 *
	 * @param   mixed  $value  Value to set
	 *
	 * @return  void
	 */
	public function setValue($value);

	/**
	 * Method to get an attribute of the field
	 *
	 * @param   string  $name     Name of the attribute to get
	 * @param   mixed   $default  Optional value to return if attribute not found
	 *
	 * @return  mixed             Value of the attribute / default
	 */
	public function getAttribute($name, $default = null);

	/**
	 * Method to get a control group with label and input.
	 *
	 * @param   array  $options  Options to be passed into the rendering of the field
	 *
	 * @return  string  A string containing the html for the control group
	 */
	public function renderField($options = array());

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @return  string  The field HTML
	 *
	 * @since 2.0
	 */
	public function getStatic();

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @return  string  The field HTML
	 *
	 * @since 2.0
	 */
	public function getRepeatable();
}
