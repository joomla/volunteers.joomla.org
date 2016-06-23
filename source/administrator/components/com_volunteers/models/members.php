<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once 'base.php';

/**
 * Class VolunteersModelReports
 */
class VolunteersModelMembers extends VolunteersModelBase
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
	protected function populateState($ordering = 'members.', $direction = 'asc')
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

		if (FOFPlatform::getInstance()->isBackend())
		{
			$query->innerJoin('#__volunteers_volunteers AS v ON member.volunteers_volunteer_id = v.volunteers_volunteer_id')
				->select('v.image as volunteer_image')
				->select('v.firstname as volunteer_firstname')
				->select('v.lastname as volunteer_lastname')
				->select('v.volunteers_volunteer_id as volunteer_id')
			;

			$query->innerJoin('#__users AS u ON v.user_id = u.id')
				->select('u.email as volunteer_email')
			;

			$query->clear('order')
				->order('v .firstname ASC');

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
		if (FOFPlatform::getInstance()->isBackend())
		{
			// Get all departments, groups, subgroups
			$db = $this->getDbo();

			$query = $db->getQuery(true);
			$query->select('*')
					->from('#__volunteers_departments');

			$db->setQuery($query);

			$departments = $db->loadObjectList('volunteers_department_id');

			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__volunteers_groups');

			$db->setQuery($query);

			$groups = $db->loadObjectList('volunteers_group_id');

			$query = $db->getQuery(true);
			$query->select('*')
				->from('#__volunteers_subgroups');

			$db->setQuery($query);

			$subgroups = $db->loadObjectList('volunteers_subgroup_id');

			foreach ($resultArray as $result)
			{
				$data = array();

				switch ($result->reltable)
				{
					case 'departments':
						$data = $departments;
						break;

					case 'groups':
						$data = $groups;
						break;

					case 'subgroups':
						$data = $subgroups;
						break;
				}

				if (array_key_exists($result->reltable_id, $data))
				{
					$result->reltoname = $data[$result->reltable_id]->title;
				}
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
			$type = $this->input->get('type', 'group');

			if ($type == 'group')
			{
				$record->type = 'group';

				$group_id = $record->reltable_id;

				if (is_null($group_id))
				{
					$group_id = $this->input->get('group', 0);
				}

				if ($group_id != 0)
				{
					$groupTable = FOFTable::getAnInstance('group', 'VolunteersTable');
					$groupTable->load($group_id);
					$record->group = $groupTable;

					$departmentTable = FOFTable::getAnInstance('department', 'VolunteersTable');
					$departmentTable->load($record->group->department_id);
					$record->department = $departmentTable;

					$record->reltable_id = $group_id;

					// Finally Check ACL
					$this->getAcl($record);
				}
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
			$type = $this->input->get('type');

			$record = $this->record;

			$form->setFieldAttribute('volunteers_volunteer_id', 'reltable', $type . 's');
			$form->setFieldAttribute('volunteers_volunteer_id', 'reltable_id', $record->group->volunteers_group_id);

			$form->removeField('enabled');
			$form->removeField('created_on');
			$form->removeField('date_started');

			if (is_null($record->volunteers_member_id))
			{
				$form->removeField('date_ended');
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
		$data['reltable'] = $data['type'] . 's';
		
		$result = parent::onBeforeSave($data, $table);

		if ($result)
		{
			$data['created_by'] = JFactory::getUser()->get('id');
		}

		return $result;
	}
}