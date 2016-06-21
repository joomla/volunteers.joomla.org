<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersModelBase extends FOFModel
{
	protected $volunteersId = null;


	protected function getVolunteerGroups($volunteer_id)
	{
		return $this->getVolunteerGroupRelations($volunteer_id, false);
	}

	protected function getVolunteerHonourroll($volunteer_id)
	{
		return $this->getVolunteerGroupRelations($volunteer_id, true);
	}
	
	protected function getVolunteerGroupRelations($volunteer_id, $honour = false)
	{
		$date = 'gm.date_ended = "0000-00-00"';
		
		if ($honour)
		{
			$date = 'gm.date_ended <> "0000-00-00"';
		}
		
		$db = $this->getDbo();
		
		$query = $db->getQuery(true);
		
		$query->select('g.*, gm.role as role, gm.position as position')
			->select('gm.volunteers_groupmember_id as volunteers_groupmember_id')
			->from('#__volunteers_groups as g')
			->from('#__volunteers_groupmembers as gm')
			->where('gm.volunteers_volunteer_id = ' . (int) $volunteer_id)
			->where('gm.volunteers_group_id = g.volunteers_group_id')
			->where($date)
			->order('g.title ASC');
		
		$db->setQuery($query);
		
		return $db->loadObjectList();
	}

	protected function getGroupVolunteers($group_id)
	{
		return $this->getGroupVolunteerRelations($group_id, false);
	}

	protected function getGroupHonourroll($group_id)
	{
		return $this->getGroupVolunteerRelations($group_id, true);
	}

	protected function getGroupVolunteerRelations($group_id, $honour = false)
	{
		$date = 'gm.date_ended = "0000-00-00"';

		if ($honour)
		{
			$date = 'gm.date_ended <> "0000-00-00"';
		}

		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->select('v.*, gm.role as role, gm.position as position')
			->select('gm.volunteers_groupmember_id as volunteers_groupmember_id')
			->select('gm.date_started as date_started, gm.date_ended as date_ended')
			->from('#__volunteers_volunteers as v')
			->from('#__volunteers_groupmembers as gm')
			->where('gm.volunteers_group_id = ' . (int) $group_id)
			->where('gm.volunteers_volunteer_id = v.volunteers_volunteer_id')
			->where($date)
			->order('v.firstname ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	protected function getGroupReports($group_id)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->select('r.*')
			->select('v.firstname as volunteer_firstname, v.lastname as volunteer_lastname, v.image as volunteer_image')
			->select('g.title as group_title')
			->from('#__volunteers_reports as r')
			->from('#__volunteers_volunteers as v')
			->from('#__volunteers_groups as g')
			->where('r.created_by = v.user_id')
			->where('r.volunteers_group_id = g.volunteers_group_id')
			->where('g.volunteers_group_id = ' . (int) $group_id)
			->order('r.created_on DESC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	protected function getSubgroup($group_id)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->select('s.*')
			->from('#__volunteers_subgroups as s')
			->where('s.group_id = ' . (int) $group_id)
			->order('s.title ASC');

		// Join the member table and members

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	protected function getDepartmentGroups($department_id)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->select('g.*')
			->from('#__volunteers_groups as g')
			->where('g.department_id = ' . (int) $department_id)
			->order('g.title ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	protected function getVolunteerId()
	{
		if (is_null($this->volunteersId))
		{
			$userId = JFactory::getUser()->get('id');

			if ($userId == 0)
			{
				$this->volunteersId = 0;

				return 0;
			}

			$volunteerTable = FOFTable::getAnInstance('volunteer', 'VolunteersTable');
			$volunteerTable->load(array('user_id' => $userId));

			$this->volunteersId = $volunteerTable->volunteers_volunteer_id;
		}

		return $this->volunteersId;
	}

	protected function isAdmin()
	{
		$user = JFactory::getUser();

		return $user->authorise('code.admin', 'com_volunteers');
	}

	public function getComponentConfiguration()
	{
		return JComponentHelper::getParams('com_volunteers');
	}
}
