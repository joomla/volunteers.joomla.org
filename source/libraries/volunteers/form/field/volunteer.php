<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * field type
 *
 * @package  volunteers
 * @since    2.0.0
 */
class VolunteersFormFieldVolunteer extends JFormFieldList
{
	/**
	 * The form field type.
	 */
	protected $type = 'Volunteer';

	/**
	 * Method to get the field options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		$options = array();

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('concat(firstname, " ", lastname, " ", email) as text, volunteers_volunteer_id as value')
			->from('#__volunteers_volunteers')
			->where('enabled = 1')
			->order('firstname, lastname');

		$db->setQuery($query);
		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}


		// Merge any additional options in the XML definition.
		$options = array_merge(
			array(JHtml::_('select.option', '0', JText::_('JOPTION_DO_NOT_USE'))),
			parent::getOptions(),
			$options);

		return $options;
	}
}
