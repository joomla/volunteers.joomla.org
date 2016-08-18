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
 * Teams Field class.
 */
class JFormFieldTeams extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Teams';

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 */
	public function getOptions()
	{
		$parent    = $this->element['parent'];
		$teams     = JHtmlVolunteers::teams($parent);
		$default[] = JHtml::_('select.option', '', JText::_('COM_VOLUNTEERS_SELECT_TEAM'));
		$options   = array_merge($default, $teams);

		return array_merge(parent::getOptions(), $options);
	}
}
