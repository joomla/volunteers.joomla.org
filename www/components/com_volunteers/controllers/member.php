<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Member controller class.
 */
class VolunteersControllerMember extends JControllerForm
{
	/**
	 * Constructor
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->addModelPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models', 'VolunteersModel');
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_volunteers/models/forms');
	}

	/**
	 * Method to add member
	 *
	 * @return  boolean
	 */
	public function add($key = null, $urlVar = null)
	{
		// Get variables
		$departmentId = $this->input->getInt('department');
		$teamId       = $this->input->getInt('team');

		// Department or team?
		if ($departmentId)
		{
			$acl = VolunteersHelper::acl('department', $departmentId);
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.departmentid', $departmentId);
		}
		elseif ($teamId)
		{
			$acl = VolunteersHelper::acl('team', $teamId);
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.teamid', $teamId);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId));
		}

		// Use parent add method
		return parent::add();
	}

	/**
	 * Method to edit member data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$memberId = $this->input->getInt('id');
		$member   = $this->getModel()->getItem($memberId);

		// Department or team?
		if ($member->department)
		{
			$acl = VolunteersHelper::acl('department', $member->department);
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.departmentid', $member->department);
		}
		elseif ($member->team)
		{
			$acl = VolunteersHelper::acl('team', $member->team);
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.teamid', $member->team);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId));
		}

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save member data.
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Get variables
		$app          = JFactory::getApplication();
		$memberId     = $this->input->getInt('id');
		$member       = $this->getModel()->getItem($memberId);
		$departmentId = (int) ($memberId) ? $member->department : $app->getUserState('com_volunteers.edit.member.departmentid');
		$teamId       = (int) ($memberId) ? $member->team : $app->getUserState('com_volunteers.edit.member.teamid');

		// Department or team?
		if ($departmentId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.departmentid', null);
			$acl = VolunteersHelper::acl('department', $departmentId);
		}
		elseif ($teamId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.teamid', null);
			$acl = VolunteersHelper::acl('team', $teamId);
		}

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId));
		}

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Redirect to the team
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_MEMBER_SAVED'));

		// Department or team?
		if ($departmentId)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
		}
		elseif ($teamId)
		{
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
		}

		return $return;
	}

	/**
	 * Method to cancel member data.
	 *
	 * @return  boolean
	 */
	public function cancel($key = null)
	{
		// Get variables
		$app          = JFactory::getApplication();
		$departmentId = $app->getUserState('com_volunteers.edit.member.departmentid');
		$teamId       = $app->getUserState('com_volunteers.edit.member.teamid');

		// Use parent save method
		$return = parent::cancel($key);

		// Department or team?
		if ($departmentId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.departmentid', null);
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
		}
		elseif ($teamId)
		{
			JFactory::getApplication()->setUserState('com_volunteers.edit.member.teamid', null);
			$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
		}

		return $return;
	}
}