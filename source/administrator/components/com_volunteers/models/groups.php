<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersModelGroups extends FOFModel
{
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');

		return (object)array(
			'id'			=> $this->getState('id',null,'int'),
			'group'			=> $this->getState('group',null,'int'),
			'active'		=> $this->getState('active',1,'int'),
			'ownership'		=> $this->getState('ownership',null,'int'),
			'enabled'		=> $enabled,
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
			if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'title';
			$dir = $this->getState('filter_order_Dir', 'ASC', 'cmd');
			$query->order($order.' '.$dir);
		}

	}

	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if(is_numeric($state->enabled)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('enabled').' = '.
					$db->q($state->enabled)
			);
		}

		if(is_numeric($state->id) && ($state->id > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_group_id').' = '.
					$db->q($state->id)
			);
		}

		if(is_numeric($state->group) && ($state->group > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('volunteers_group_id').' = '.
					$db->q($state->group)
			);
		}

		if(is_numeric($state->ownership) && ($state->ownership > 0)) {
			$query->where(
				$db->qn('tbl').'.'.$db->qn('ownership').' = '.
					$db->q($state->ownership)
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
			->from($db->quoteName('#__volunteers_groups').' AS '.$db->qn('tbl'));

		$this->_buildQueryColumns($query);
		$this->_buildQueryWhere($query);

		return $query;
	}
}