<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersModelVolunteers extends FOFModel
{
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');

		return (object)array(
			'id'			=> $this->getState('id',null,'int'),
			'volunteer'		=> $this->getState('volunteer',null,'int'),
			'user_id'		=> $this->getState('user_id',null,'int'),
		);
	}

	protected function _buildQueryColumns($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		$query->select(array(
			$db->qn('tbl').'.*',
		));

		$order = $this->getState('filter_order', 'random', 'cmd');

		if($order =='random')
		{
			$query->order('RAND()');
		}
		else
		{
			if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'firstname';
			$dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
			$query->order($order.' '.$dir);
		}

	}

	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if(is_numeric($state->id) && ($state->id > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_volunteer_id').' = '.
					$db->q($state->id)
			);
		}

		if(is_numeric($state->volunteer) && ($state->volunteer > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_volunteer_id').' = '.
					$db->q($state->volunteer)
			);
		}

		if(is_numeric($state->user_id) && ($state->user_id > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('user_id').' = '.
					$db->q($state->user_id)
			);
		}
	}

	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = FOFQueryAbstract::getNew($db)
			->from($db->quoteName('#__volunteers_volunteers').' AS '.$db->qn('tbl'));

		$this->_buildQueryColumns($query);
		$this->_buildQueryWhere($query);

		return $query;
	}
}