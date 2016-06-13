<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersModelGroupmembers extends FOFModel
{
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');

		return (object)array(
			'id'			=> $this->getState('id',null,'int'),
			'group'			=> $this->getState('group',null,'int'),
			'volunteer'		=> $this->getState('volunteer',null,'int'),
			'active'		=> $this->getState('active',1,'int'),
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
			$db->qn('v').'.'.$db->qn('user_id').' AS '.$db->qn('volunteer_user_id'),
			$db->qn('v').'.'.$db->qn('image').' AS '.$db->qn('volunteer_image'),
			$db->qn('g').'.'.$db->qn('title').' AS '.$db->qn('group_title'),
		));

		$order = $this->getState('filter_order', 'volunteer_firstname', 'cmd');

		if($order =='random')
		{
			$query->order('RAND()');
		}
		else
		{
			if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'volunteer_firstname';
			$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
			$query->order($order.' '.$dir);
		}
	}

	protected function _buildQueryJoins($query)
	{
		$db = $this->getDbo();

		$query
			->join('LEFT OUTER', $db->qn('#__volunteers_volunteers').' AS '.$db->qn('v').' ON '.
					$db->qn('tbl').'.'.$db->qn('volunteers_volunteer_id').' = '.
					$db->qn('v').'.'.$db->qn('volunteers_volunteer_id'))
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
				$db->qn('tbl').'.'.$db->qn('volunteers_groupmember_id').' = '.
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

		if($state->active == 1) {
			$now = JDate::getInstance('now');
			$query->where(
				$db->qn('tbl').'.'.$db->qn('date_ended').' IS NULL'
			);
		}

		if($state->active == 0) {
			$now = JDate::getInstance('now');
			$query->where(
				$db->qn('tbl').'.'.$db->qn('date_ended').' > '.
					$db->q('0000-00-00')
			);
		}
	}

	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = FOFQueryAbstract::getNew($db)
			->from($db->quoteName('#__volunteers_groupmembers').' AS '.$db->qn('tbl'));

		$this->_buildQueryColumns($query);
		$this->_buildQueryJoins($query);
		$this->_buildQueryWhere($query);

		return $query;
	}
}