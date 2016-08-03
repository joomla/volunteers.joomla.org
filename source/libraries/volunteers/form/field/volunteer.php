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
		$options     = array();
		$excludedIds = array();
		$db		     = JFactory::getDbo();
		$query	     = $db->getQuery(true);

		$reltable      = $this->getAttribute('reltable');
		$reltable_id   = $this->getAttribute('reltable_id');

		// Values: groupmember,teamleader,assistantteamleader
		$exclude       = explode(',', $this->getAttribute('exclude'));

		if (in_array('groupmember', $exclude) && ! empty($reltable_id))
		{
			$query->select('volunteers_volunteer_id')
					->from('#__volunteers_members AS m')
					->where('m.reltable_id =' . (int) $reltable_id)
					->where('m.reltable = ' . $db->q($reltable))
					->where('m.ns_position in (0,1)');

			$db->setQuery($query);

			$ids = $db->loadColumn();

			$excludedIds = array_merge($excludedIds, $ids);
		}

		if ((in_array('teamleader', $exclude) || in_array('assistantteamleader', $exclude)) && ! empty($reltable_id) && ! empty($reltable))
		{
			$field = substr($reltable, 0, strlen($reltable)-1);

			$query->select('lead, assistant1, assistant2')
				->from('#__volunteers_' . $reltable . 'AS ex')
				->where('ex.volunteers_' . $field . '_id =' . (int) $reltable_id)
				->where('ex.reltable = ' . $db->q($reltable));

			$db->setQuery($query);

			$obj = $db->loadObject();

			$ids = array();

			$toExclude = array();

			if (in_array('teamleader', $exclude))
			{
				$toExclude[] = 'lead';
			}

			if (in_array('assistantteamleader', $exclude))
			{
				$toExclude[] = 'assistant1';
				$toExclude[] = 'assistant2';
			}

			foreach ($toExclude AS $col)
			{
				if ($obj->$col != 0)
				{
					$ids[] = $obj->$col;
				}
			}

			$excludedIds = array_merge($excludedIds, $ids);
		}

		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

		$query->select('concat(v.firstname, " ", v.lastname, " ", v.email) as text, v.volunteers_volunteer_id as value')
			->from('#__volunteers_volunteers AS v')
			->where('v.enabled = 1')
			->order('v.firstname, v.lastname');
		
		$filter = $this->getAttribute('filtergroup');
		
		if ( ! empty($filter) && ! empty($reltable_id))
		{
			$query->from('#__volunteers_members AS m')
				->where('m.volunteers_volunteer_id = v.volunteers_volunteer_id')
				->where('m.reltable_id =' . (int) $reltable_id)
				->where('m.reltable = ' . $db->q($reltable));
		}
		
		if ( ! empty($excludedIds))
		{
			$query->where('v.volunteers_volunteer_id not in (' . implode(',', $excludedIds) . ')');
		}

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
