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
class VolunteersModelReports extends VolunteersModelBase
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
	protected function populateState($ordering = 'report.created', $direction = 'desc')
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
				->order('report.created_on DESC');

			$query->innerJoin('#__volunteers_volunteers AS v ON report.created_by = v.user_id')
				->select('v.image as volunteer_image')
				->select('v.firstname as volunteer_firstname')
				->select('v.lastname as volunteer_lastname')
				->select('v.volunteers_volunteer_id as volunteer_id')
			;

			$query->innerJoin('#__volunteers_groups AS g ON report.volunteers_group_id = g.volunteers_group_id')
				->select('g.title as group_title')
				->select('g.volunteers_group_id as group_id')
			;
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
			$record->volunteer = null;
			$record->group     = null;

			if ($record->created_by != 0)
			{
				$volunteerTable = FOFTable::getAnInstance('volunteer','VolunteersTable');
				$volunteerTable->load(array('user_id' => $record->created_by ));
				$record->volunteer    = $volunteerTable;
			}

			$group_id = $record->volunteers_group_id;

			if (is_null($group_id))
			{
				$group_id = $this->input->get('group', 0);
			}

			if ($group_id != 0)
			{
				$groupTable = FOFTable::getAnInstance('group','VolunteersTable');
				$groupTable->load($group_id);
				$record->group = $groupTable;
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
			$form->removeField('slug');
			$form->removeField('enabled');
			$form->removeField('created_on');
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

		if ($result)
		{
			$data['created_by'] = JFactory::getUser()->get('id');
		}

		return $result;
	}
}