<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once 'base.php';

class VolunteersModelGroups extends VolunteersModelBase
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'group.volunteers_group_id',
				'title', 'group.title',
				'enabled', 'group.enabled'
			);
		}

		parent::__construct($config);
	}

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
	protected function populateState($ordering = 'group.title', $direction = 'asc')
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
				->order('group.title ASC');
		}

		return $query;
	}
	
	/**
	 * This method can be overriden to automatically do something with the
	 * list results array. You are supposed to modify the list which was passed
	 * in the parameters; DO NOT return a new array!
	 *
	 * @param   array  &$resultArray  An array of objects, each row representing a record
	 *
	 * @return  void
	 */
	protected function onProcessList(&$resultArray)
	{
		$db = JFactory::getDbo();
		$membersQuery = $db->getQuery(true);
		
		$membersQuery->select('*')
			->from('#__volunteers_volunteers AS volunteer')
			->from('#__volunteers_members AS g2m')
			->where('g2m.volunteers_volunteer_id = volunteer.volunteers_volunteer_id')
			->where('g2m.reltable = "groups"')
			->where('volunteer.enabled = 1')
			->where('g2m.date_ended = "0000-00-00"')
			->order('RAND()');
		
		$db->setQuery($membersQuery);
		
		$members = $db->loadObjectList();

		if (empty($members))
		{
			return true;
		}

		foreach($members as $member)
		{
			$groupmembers[$member->reltable_id][] = $member;
		}

		foreach ($resultArray as $result)
		{
			$result->members = array();

			if (array_key_exists($result->volunteers_group_id, $groupmembers))
			{
				$result->members = $groupmembers[$result->volunteers_group_id];
			}
		}
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
			$record->groupmembers = $this->getGroupVolunteers($record->volunteers_group_id);
			$record->honourroll   = $this->getGroupHonourroll($record->volunteers_group_id);
			$record->reports      = $this->getGroupReports($record->volunteers_group_id);

			$departmentTable = FOFTable::getAnInstance('department','VolunteersTable');
			$departmentTable->load($record->department_id);
			$record->department = $departmentTable;

			// Check ACL
			$this->getAcl($record);

			$subgroupsAndAssignedMembers = array();

			$subgroups = $this->getSubgroup($record->volunteers_group_id);

			foreach ($subgroups AS $subgroup)
			{
				if ($subgroup->enabled || $record->acl->allowAddSubgroups)
				{
					$assignedMembers = $this->getSubgroupVolunteerRelations($subgroup->volunteers_subgroup_id);

					$subgroup->members = $assignedMembers;

					$subgroupsAndAssignedMembers[] = $subgroup;
				}
			}

			$record->subgroups = $subgroupsAndAssignedMembers;

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

			$form->removeField('department_id');
			$form->removeField('state');
			$form->removeField('date_started');
			$form->removeField('date_ended');
			$form->removeField('enabled');
			$form->removeField('notes');
			$form->removeField('lead');

			if (! $item->acl->teamLeader)
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
	protected function onBeforeSave(&$data, &$table)
	{
		$result = parent::onBeforeSave($data, $table);

		if ($result && $data['ready4transition'] == 1 && $data['ready4transitiondate'] == '0000-00-00 00:00:00' )
		{
			$data['ready4transitiondate'] = JFactory::getDate()->toSql();
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
		$acl->member = false;

		$acl->allowAddMembers   = false;
		$acl->allowAddreports   = false;
		$acl->allowAddSubgroups = false;
		$acl->allowEditgroup    = false;

		$vId = $this->getVolunteerId();

		if ($vId)
		{
			$acl->admin = $this->isAdmin();

			$department = $record->department;
			$acl->departmentCoordinator = $department->lead == $vId || $department->assistant1 == $vId || $department->assistant2 == $vId;

			$group = $record;
			$acl->teamLeader = $group->lead == $vId;

			$acl->assistantTeamLeader = $group->assistant1 == $vId || $group->assistant2 == $vId;

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
			$acl->allowAddSubgroups = $acl->teamLeader || $acl->assistantTeamLeader;
			$acl->allowEditgroup    = $acl->teamLeader || $acl->assistantTeamLeader;
		}

		$record->acl = $acl;
	}
}
