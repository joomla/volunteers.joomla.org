<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Class VolunteersModelBase
 */
class VolunteersModelBase extends FOFModel
{
	protected $volunteersId = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 */
	protected function populateState($ordering = 'a.id', $direction = 'asc')
	{
		$this->setState('filter_order', $ordering);
		$this->setState('filter_order_dir', $direction);
	}

	protected function getVolunteerGroups($volunteer_id)
	{
		return $this->getVolunteerRelations($volunteer_id);
	}

	protected function getVolunteerHonourroll($volunteer_id)
	{
		return $this->getVolunteerRelations($volunteer_id, 'group', true);
	}
	
	/**
	 * Returns information about the relation of a given volunteer to a subgroup|group|department
	 *
	 * @param   integer  $volunteer_id  The volunteer id
	 * @param   string   $type          The type of the relation
	 * @param   bool     $honour        List past relations for group|department
	 *
	 * @return  bool|mixed
	 */
	protected function getVolunteerRelations($volunteer_id, $type = 'group', $honour = false)
	{
		if ( ! in_array($type, array('subgroup', 'group', 'department')))
		{
			return false;
		}

		$date = $this->getPastRelationFilter($honour);

		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		$query->select('a.*')
			->select('rel.role as role, rel.position as position, rel.ns_role as ns_role, rel.ns_position as ns_position')
			->select('rel.volunteers_member_id as volunteers_member_id')
			->select('rel.date_started as date_started, rel.date_ended as date_ended')
			->from('#__volunteers_' . $type . 's as a')
			->from('#__volunteers_members as rel')
			->where('rel.volunteers_volunteer_id = ' . (int) $volunteer_id)
			->where('rel.reltable_id = a.volunteers_' . $type . '_id')
			->where($date)
			->where('rel.reltable =' .$db->q($type . 's'))
			->order('a.title ASC');

		$db->setQuery($query);
		
		return $db->loadObjectList();
	}

	protected function getGroupVolunteers($group_id)
	{
		return $this->getGroupRelations($group_id);
	}

	protected function getGroupHonourroll($group_id)
	{
		return $this->getGroupRelations($group_id, 'group', true);
	}

	protected function getGroupRelations($id, $type = 'group', $honour = false)
	{
		if ( ! in_array($type, array('subgroup', 'group', 'department')))
		{
			return false;
		}

		$date = $this->getPastRelationFilter($honour);

		$db = $this->getDbo();

		$query = $db->getQuery(true);

		$query->select('a.*')
			->select('rel.role as role, rel.position as position, rel.ns_role as ns_role, rel.ns_position as ns_position')
			->select('rel.volunteers_member_id as volunteers_member_id')
			->select('rel.date_started as date_started, rel.date_ended as date_ended')
			->from('#__volunteers_volunteers as a')
			->from('#__volunteers_members as rel')
			->where('rel.reltable_id = ' . (int) $id)
			->where('rel.volunteers_volunteer_id = a.volunteers_volunteer_id')
			->where($date)
			->where('rel.reltable =' .$db->q($type . 's'))
			->order('a.firstname ASC');

		$db->setQuery($query);

		return $db->loadObjectList();
	}

	protected function getSubgroupVolunteerRelations($id)
	{
		return $this->getGroupRelations($id, 'subgroup');
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

	/**
	 * @param $honour
	 *
	 * @return string
	 */
	private function getPastRelationFilter($honour)
	{
		if ($honour)
		{
			return 'rel.date_ended <> "0000-00-00"';
		}

		return 'rel.date_ended = "0000-00-00"';
	}
}
