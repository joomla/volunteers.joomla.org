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
}