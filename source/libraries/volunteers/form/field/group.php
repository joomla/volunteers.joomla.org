<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

JFormHelper::loadFieldClass('list');

/**
 * field group
 *
 * @package  volunteers
 * @since    2.0.0
 */
class VolunteersFormFieldGroup extends JFormFieldList
{
	/**
	 * The form field type.
	 */
	protected $type = 'Group';

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

		$query->select('title as text, volunteers_group_id as value')
			->from('#__volunteers_groups')
		;

		$db->setQuery($query);
		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			JError::raiseWarning(500, $db->getErrorMsg());
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(
			array(  JHtml::_('select.option', '0', '- none -')),
					parent::getOptions(),
					$options
			);

		return $options;
	}
}
