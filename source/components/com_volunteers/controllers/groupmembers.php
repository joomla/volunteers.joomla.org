<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

class VolunteersControllerGroupmembers extends FOFController
{
	protected function onBeforeAdd()
	{
		$roles = $this->_getRoles();

		$this->getThisView()->assign('roles', $roles);

		if($roles->lead || $roles->liaison)
		{
			return true;
		}
	}

	protected function onBeforeEdit()
	{
		$roles = $this->_getRoles();

		$this->getThisView()->assign('roles', $roles);

		if($roles->lead || $roles->liaison || $roles->owner)
		{
			return true;
		}
	}

	public function onBeforeSave()
	{
		return true;
	}

	/**
	 * Save the reply
	 */
	public function save()
	{
		// Get Input
		$member 	= $this->input->getInt('volunteers_groupmember_id',0);
		$volunteer 	= $this->input->getInt('volunteers_volunteer_id',0);
		$group 		= $this->input->getInt('volunteers_group_id',0);

		// Check if Group member already exists
		$groupmember = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
			->volunteer($volunteer)
			->group($group)
			->getFirstItem();


		if($member == 0 && $groupmember->volunteers_groupmember_id)
		{
			// Exists, show error
			$msg  = JText::_('COM_VOLUNTEERS_LBL_GROUPMEMBER_EXISTS');
			$type = 'error';
		}
		else
		{
			// New, save data
			$model  = $this->getThisModel();
			$data   = $this->input->getData();
			$result = $model->save($data);


			$msg  = JText::_('COM_VOLUNTEERS_LBL_GROUPMEMBER_SAVED');
			$type = 'success';
		}

		$url = JRoute::_('index.php?option=com_volunteers&view=group&id='.$group);

		$this->setRedirect($url, $msg, $type);
	}

	/**
	 * Get the roles for the current ticket
	 * @return array
	 */
	protected function _getRoles()
	{
		// Prepare roles
		$roles = new stdClass();
		$roles->member 	= false;
		$roles->lead 	= false;
		$roles->liaison = false;
		$roles->owner 	= false;

		// Get current User Id
		$user_id 		= JFactory::getUser()->id;

		// Get Groupmember info
		$groupmember 	= $this->getThisModel()->getItem()->getData();
		$role 			= $groupmember['role'];
		$group_id		= $groupmember['volunteers_group_id'];
		$volunteer_id	= $groupmember['volunteers_volunteer_id'];

		if(empty($group_id))
		{
			$group_id = JFactory::getApplication()->input->get('group', 0);
		}

		if(isset($volunteer_id))
		{
			// Get Volunteer Profile owner
			$owner = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
				->setID($volunteer_id)
				->getItem();

			// Is this the owner of the profile?
			if($owner->user_id == $user_id)
			{
				$roles->owner 		= true;
			}
		}

		// Get Volunteer Profile Manager
		$manager = FOFModel::getTmpInstance('Volunteers', 'VolunteersModel')
			->user_id($user_id)
			->getFirstItem();

		// Get groupmember info
		$groupmember = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
				->volunteer($manager->volunteers_volunteer_id)
				->group($group_id)
				->getFirstItem();

		// Group Member
		if($groupmember->role == 1)
		{
			$roles->member 		= true;
		}

		// Group Lead
		if($groupmember->role == 2)
		{
			$roles->member 		= true;
			$roles->lead 		= true;
		}

		// Group Liaison
		if(($groupmember->role == 3) || ($groupmember->role == 4) || ($groupmember->role == 5))
		{
			$roles->member 		= true;
			$roles->lead 		= true;
			$roles->liaison 	= true;
		}

		// Get Group data
		$group = FOFModel::getTmpInstance('Groups', 'VolunteersModel')
			->setID($group_id)
			->getItem();

		// Leadership team
		if($groupmember->role == 1 && $group->ownership == 1)
		{
			$roles->member 		= true;
			$roles->lead 		= true;
			$roles->liaison 	= true;
		}

		// Is this user a leadership liaison?
		$liaison = FOFModel::getTmpInstance('Groupmembers', 'VolunteersModel')
				->volunteer($manager->volunteers_volunteer_id)
				->group($group->ownership)
				->getFirstItem();

		if($liaison->role == 1)
		{
			$roles->liaison 	= true;
		}

		return $roles;
	}
}