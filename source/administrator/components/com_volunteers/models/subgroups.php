<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once 'base.php';

class VolunteersModelSubgroups extends VolunteersModelBase
{
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
	protected function populateState($ordering = 'subgroup.title', $direction = 'desc')
	{
		parent::populateState($ordering, $direction);
	}
	
	/**
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);

		if (FOFPlatform::getInstance()->isFrontend())
		{
			$query->where('enabled = 1');

			$query->clear('order')
				->order('subgroup.title ASC');
		}

		if (FOFPlatform::getInstance()->isBackend())
		{
			$query->select('g.title AS gtitle')
				->from('#__volunteers_groups AS g')
				->where('subgroup.group_id = g.volunteers_group_id');
		}

		return $query;
	}

	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return  void
	 */
	protected function onAfterGetItem(&$record)
	{
		parent::onAfterGetItem($record);

		if (FOFPlatform::getInstance()->isFrontend())
		{
			$record->groupmembers = array();
				
			$group_id = $record->group_id;
			
			if (is_null($group_id))
			{
				$group_id = $this->input->get('group', 0);
			}
			
			if ($group_id != 0)
			{
				$record->groupmembers = $this->getGroupVolunteers($group_id);

				$groupTable = FOFTable::getAnInstance('group','VolunteersTable');
				$groupTable->load($group_id);
				$record->group = $groupTable;

				$departmentTable = FOFTable::getAnInstance('department','VolunteersTable');
				$departmentTable->load($record->group->department_id);
				$record->department = $departmentTable;

				// Finally Check ACL
				$this->getAcl($record);
			}
			
			$record->subgroupmembers = $this->getSubgroupVolunteerRelations($record->volunteers_subgroup_id);

			$record->subgroupmemberIds = array();

			foreach ($record->subgroupmembers AS $subgroupmember)
			{
				$record->subgroupmemberIds[] = $subgroupmember->volunteers_volunteer_id;
			}
		}
	}

	/**
	 * Allows data and form manipulation after preprocessing the form
	 *
	 * @param   FOFForm  $form    A FOFForm object.
	 * @param   array    &$data   The data expected for the form.
	 * @codeCoverageIgnore
	 *
	 * @return  void
	 */
	public function onAfterPreprocessForm(FOFForm &$form, &$data)
	{
		if (FOFPlatform::getInstance()->isFrontend())
		{
			$item = $this->getItem();

			$form->removeField('group_id');
			$form->removeField('state');
			$form->removeField('slug');
			$form->removeField('acronym');
			$form->removeField('date_started');
			$form->removeField('date_ended');
			if (! $item->acl->teamLeader && ! $item->acl->assistantTeamLeader)
			{
				$form->removeField('lead');
				$form->removeField('enabled');
				$form->removeField('notes');
			}

			if (! $item->acl->teamLeader && ! $item->acl->assistantTeamLeader && ! $item->acl->subteamLeader)
			{
				$form->removeField('assistant1');
				$form->removeField('assistant2');
			}
		}
	}

	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 */
	protected function onAfterSave(&$table)
	{
		$result = parent::onAfterSave($table);

		if  ($result)
		{
			$savedAssignedMembers = $this->record->subgroupmemberIds;
			$toBeAssigendMembers = (array) $this->input->get('assigned');

			$unchanged = array_intersect($savedAssignedMembers, $toBeAssigendMembers);
			$toDelete  = array_diff($savedAssignedMembers, $unchanged);
			$toAdd     = array_diff($toBeAssigendMembers, $unchanged);

			if ( ! empty($toDelete))
			{
				$db    = $this->getDbo();
				$query = $db->getQuery(true);
				
				$query->delete('#__volunteers_members')
					->where('reltable = "subgroups"')
					->where('reltable_id = ' . (int) $table->volunteers_subgroup_id)
					->where('volunteers_volunteer_id in (' . implode(',', $toDelete) . ')');

				$db->setQuery($query);

				$db->execute();
			}	
			
			$membersTable =  FOFTable::getAnInstance('member','VolunteersTable');

			foreach ($toAdd as $add)
			{
				// Force to make a new entry
				$membersTable->volunteers_member_id = null;

				$membersTable->reltable    = 'subgroups';
				$membersTable->reltable_id = $table->volunteers_subgroup_id;
				$membersTable->enabled     = 1;
				$membersTable->volunteers_volunteer_id = $add;
				
				$membersTable->store();
			}
		}

		return $result;
	}

	protected function getAcl(&$record)
	{
		$acl = new stdClass;

		$acl->admin = false;
		$acl->departmentCoordinator = false;
		$acl->teamLeader = false;
		$acl->assistantTeamLeader = false;
		$acl->subteamLeader = false;
		$acl->assistantSubteamLeader = false;
		$acl->member = false;

		$acl->allowEditSubgroup    = false;

		$vId = $this->getVolunteerId();

		if ($vId)
		{
			$acl->admin = $this->isAdmin();

			$department = $record->department;
			$acl->departmentCoordinator = $department->lead == $vId || $department->assistant1 == $vId || $department->assistant2 == $vId;

			$group = $record->group;
			$acl->teamLeader = $group->lead == $vId;

			$acl->assistantTeamLeader = $group->assistant1 == $vId || $group->assistant2 == $vId;

			$subgroup = $record;
			$acl->subteamLeader = $subgroup->lead == $vId;

			$acl->assistantSubteamLeader = $subgroup->assistant1 == $vId || $subgroup->assistant2 == $vId;

			$found = false;
			$gm = $record->groupmembers;

			reset($gm);

			while ((list(, $group) = each($gm)) && ! $found)
			{
				$found = $group->volunteers_volunteer_id == $vId;
			}

			$acl->member = $found;

			$acl->allowAddMembers   = $acl->admin || $acl->departmentCoordinator || $acl->teamLeader || $acl->assistantTeamLeader;
			$acl->allowAddreports   = $acl->teamLeader || $acl->assistantTeamLeader || $acl->member;
			$acl->allowEditSubgroup = $acl->teamLeader || $acl->assistantTeamLeader || $acl->subteamLeader || $acl->assistantSubteamLeader;
		}

		$record->acl = $acl;
	}

}