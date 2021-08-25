<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.List
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JLoader::import('components.com_fields.libraries.fieldslistplugin', JPATH_ADMINISTRATOR);

/**
 * Fields list Plugin
 *
 * @since  3.7.0
 */
class PlgFieldsList extends FieldsListPlugin
{
	/**
	 * Prepares the field
	 *
	 * @param   string    $context  The context.
	 * @param   stdclass  $item     The item.
	 * @param   stdclass  $field    The field.
	 *
	 * @return  object
	 *
	 * @since   3.9.2
	 */
	public function onCustomFieldsPrepareField($context, $item, $field)
	{
		// Check if the field should be processed
		if (!$this->isTypeSupported($field->type))
		{
			return;
		}

		// The field's rawvalue should be an array
		if (!is_array($field->rawvalue))
		{
			$field->rawvalue = (array) $field->rawvalue;
		}

		return parent::onCustomFieldsPrepareField($context, $item, $field);
	}
}
