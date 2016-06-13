<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerGroups extends FOFController
{
	public function onBeforeRead() {

		$group = $this->getThisModel()->getItem()->volunteers_group_id;

		$groupmembers = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
			->limit(0)
			->limitstart(0)
			->enabled(1)
			->active(1)
			->group($group)
			->filter_order('firstname')
			->filter_order_Dir('ASC')
			->getList();

		$this->getThisView()->assign('groupmembers', $groupmembers);

		$honorroll = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
			->limit(0)
			->limitstart(0)
			->enabled(1)
			->active(0)
			->group($group)
			->filter_order('firstname')
			->filter_order_Dir('ASC')
			->getList();

		$this->getThisView()->assign('honorroll', $honorroll);

		$reports = FOFModel::getTmpInstance('Reports', 'VolunteersModel')
			->limit(10)
			->limitstart(0)
			->enabled(1)
			->group($group)
			->filter_order('created_on')
			->filter_order_Dir('DESC')
			->getList();

		$this->getThisView()->assign('reports', $reports);

		// Fetch roles
		$roles = $this->_getRoles();

		$this->getThisView()->assign('roles', $roles);

		return true;
	}

	/**
	 * This runs before the browse() method. Return false to prevent executing
	 * the method.
	 *
	 * @return bool
	 */
	public function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		if($result) {
			// Get the current order by column
			$orderby = $this->getThisModel()->getState('filter_order','');
			// If it's not one of the allowed columns, force it to be the "ordering" column
			if(!in_array($orderby, array('title','firstname','random'))) {
				$orderby = 'title';
			}

			// Get the event ID
			$params = JFactory::getApplication()->getPageParameters('com_volunteers');
			$eventid = $params->get('eventid', 0);

			// Apply ordering and filter only the enabled items
			$this->getThisModel()
				->filter_order($orderby)
				->enabled(1)
				->event($eventid)
				->filter_order_Dir('ASC');

			// If no groups are shown even though I do have groups, use a limitstart of 0
			if($this->input->getInt('limitstart') == '')
            {
				$this->getThisModel()->limitstart(0);
			}

			$members = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
				->limit(0)
				->limitstart(0)
				->enabled(1)
				->active(1)
				->filter_order('random')
				->getList();

			$groupmembers = array();
			foreach($members as $member)
			{
				$groupmembers[$member->volunteers_group_id][] = $member;
			}

			$this->getThisView()->assign('groupmembers', $groupmembers);

			// Fetch page parameters
			$params = JFactory::getApplication()->getPageParameters('com_volunteers');

			// Push page parameters
			$this->getThisView()->assign('pageparams', $params);
		}
		return $result;
	}

	protected function onBeforeEdit()
	{
		$roles = $this->_getRoles();

		if($roles->lead || $roles->liaison)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Save the incoming data
	 */
	public function onAfterSave()
	{
		$group = $this->getThisModel()->getItem();

		// Redirect
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=group&id='.$group->volunteers_group_id), JText::_('COM_VOLUNTEERS_LBL_GROUP_SAVED'),'success');

		return true;
	}


	public function onBeforeSave()
	{
		return true;
	}

	/**
	 * Get the roles for the current ticket
	 * @return array
	 */
	protected function _getRoles()
	{
		$roles = new stdClass();
		$roles->member 	= false;
		$roles->lead 	= false;
		$roles->liaison = false;

		// Return if not logged in
		if(!JFactory::getUser()->id)
		{
			return $roles;
		}

		// Get Volunteer
		$volunteer = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
			->user_id(JFactory::getUser()->id)
			->getFirstItem();

		// Get Group
		$group = $this->getThisModel()->getItem();

		if($group->volunteers_group_id)
		{
			// Get groupmember info
			$groupmember = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
					->volunteer($volunteer->volunteers_volunteer_id)
					->group($group->volunteers_group_id)
					->getFirstItem();

			if($groupmember->role == 1)
			{
				$roles->member 		= true;
			}
			if($groupmember->role == 2)
			{
				$roles->member 		= true;
				$roles->lead 		= true;
			}
			if(($groupmember->role == 3) || ($groupmember->role == 4) || ($groupmember->role == 5))
			{
				$roles->member 		= true;
				$roles->lead 		= true;
				$roles->liaison 	= true;
			}
			if($groupmember->role == 1 && $group->ownership == 1)
			{
				$roles->member 		= true;
				$roles->lead 		= true;
				$roles->liaison 	= true;
			}

			// Get leadership liaisons info
			$liaison = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
					->volunteer($volunteer->volunteers_volunteer_id)
					->group($group->ownership)
					->getFirstItem();

			if($liaison->role == 1)
			{
				$roles->liaison 	= true;
			}
		}

		return $roles;
	}
}