<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form;

defined('_JEXEC') or die;

/**
 * Generic interface that a FOF header field class must implement
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
interface HeaderInterface
{
	/**
	 * Method to attach a Form object to the header.
	 *
	 * @param  Form  $form  The Form object to attach to the form field.
	 *
	 * @return  HeaderInterface  The form field object so that the method can be used in a chain.
	 *
	 * @since   2.0
	 */
	public function setForm(Form $form);

	/**
	 * Method to attach a Form object to the header.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <header /> tag for the form header object.
	 * @param   string             $group    The header name group control value. This acts as as an array container for the header.
	 *                                       For example if the header has name="foo" and the group value is set to "bar" then the
	 *                                       full header name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 */
	public function setup(\SimpleXMLElement $element, $group = null);
}
