<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersModelReports extends FOFModel
{
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');

		return (object)array(
			'id'			=> $this->getState('id',null,'int'),
			'group'			=> $this->getState('group',null,'int'),
			'volunteer'		=> $this->getState('volunteer',null,'int'),
			'enabled'		=> $enabled,
		);
	}

	protected function _buildQueryColumns($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		$query->select(array(
			$db->qn('tbl').'.*',
			$db->qn('v').'.'.$db->qn('firstname').' AS '.$db->qn('volunteer_firstname'),
			$db->qn('v').'.'.$db->qn('lastname').' AS '.$db->qn('volunteer_lastname'),
			$db->qn('v').'.'.$db->qn('image').' AS '.$db->qn('volunteer_image'),
			$db->qn('v').'.'.$db->qn('volunteers_volunteer_id').' AS '.$db->qn('volunteer_id'),
			$db->qn('g').'.'.$db->qn('volunteers_group_id').' AS '.$db->qn('group_id'),
			$db->qn('g').'.'.$db->qn('title').' AS '.$db->qn('group_title'),
		));

		$order = $this->getState('filter_order', 'volunteers_report_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'volunteers_report_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

	}

	protected function _buildQueryJoins($query)
	{
		$db = $this->getDbo();

		$query
			->join('LEFT OUTER', $db->qn('#__volunteers_volunteers').' AS '.$db->qn('v').' ON '.
					$db->qn('tbl').'.'.$db->qn('created_by').' = '.
					$db->qn('v').'.'.$db->qn('user_id'))
			->join('LEFT OUTER', $db->qn('#__volunteers_groups').' AS '.$db->qn('g').' ON '.
					$db->qn('tbl').'.'.$db->qn('volunteers_group_id').' = '.
					$db->qn('g').'.'.$db->qn('volunteers_group_id'))
		;

	}

	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if(is_numeric($state->id) && ($state->id > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_report_id').' = '.
					$db->q($state->id)
			);
		}

		if(is_numeric($state->group) && ($state->group > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_group_id').' = '.
					$db->q($state->group)
			);
		}

		if(is_numeric($state->volunteer) && ($state->volunteer > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_volunteer_id').' = '.
					$db->q($state->volunteer)
			);
		}

		if(is_numeric($state->enabled)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('enabled').' = '.
					$db->q($state->enabled)
			);
		}
	}

	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = FOFQueryAbstract::getNew($db)
			->from($db->quoteName('#__volunteers_reports').' AS '.$db->qn('tbl'));

		$this->_buildQueryColumns($query);
		$this->_buildQueryJoins($query);
		$this->_buildQueryWhere($query);

		return $query;
	}
}