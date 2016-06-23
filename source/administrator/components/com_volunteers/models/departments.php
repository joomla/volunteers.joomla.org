<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

include_once 'base.php';

class VolunteersModelDepartments extends VolunteersModelBase
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
	protected function populateState($ordering = 'department.title', $direction = 'asc')
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
			$query->clear('order')
					->order('department.title ASC');
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
			$record->groups = $this->getDepartmentGroups($record->volunteers_department_id);

			$record->teamlead = null;
			$record->teamassistant1 = null;
			$record->teamassistant2 = null;

			$v = FOFTable::getAnInstance('volunteer','VolunteersTable');

			if (! empty($record->lead))
			{

				$v->load($record->lead);
				$record->teamlead = $v;

				$v = $v->getClone();
			}

			if (! empty($record->assistant1))
			{
				$v->load($record->assistant1);
				$record->teamassistant1 = $v;

				$v = $v->getClone();
			}

			if (! empty($record->assistant2))
			{
				$v->load($record->assistant2);
				$record->teamassistant2 = $v;
			}
		}
	}
}