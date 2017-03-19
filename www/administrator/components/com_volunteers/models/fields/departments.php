<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');

JLoader::register('JHtmlVolunteers', JPATH_ADMINISTRATOR . '/components/com_volunteers/helpers/html/volunteers.php');

/**
 * Departments Field class.
 */
class JFormFieldDepartments extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Departments';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	public function getOptions()
	{
		$teams     = JHtmlVolunteers::departments();
		$default[] = JHtml::_('select.option', '', JText::_('COM_VOLUNTEERS_SELECT_DEPARTMENT'));
		$options   = array_merge($default, $teams);

		return array_merge(parent::getOptions(), $options);
	}
}
