<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

/**
 * Roless Field class.
 */
class JFormFieldRoles extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Roles';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	public function getOptions()
	{
		$options   = JHtmlVolunteers::roles();
		$default[] = JHtml::_('select.option', '', JText::_('COM_VOLUNTEERS_SELECT_ROLE'));

		return array_merge($default, $options);
	}
}
