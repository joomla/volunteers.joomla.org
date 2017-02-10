<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Role controller class.
 */
class VolunteersControllerRole extends JControllerForm
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
	 * Method to add role
	 *
	 * @return  boolean
	 */
	public function add($key = null, $urlVar = null)
	{
		// Get variables
		$teamId = $this->input->getInt('team');
		$acl    = VolunteersHelper::acl('team', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Set team
		JFactory::getApplication()->setUserState('com_volunteers.edit.role.teamid', $teamId);

		// Use parent add method
		return parent::add();
	}

	/**
	 * Method to edit role data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$roleId = $this->input->getInt('id');
		$teamId = (int) $this->getModel()->getItem($roleId)->team;
		$acl    = VolunteersHelper::acl('team', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Set team
		JFactory::getApplication()->setUserState('com_volunteers.edit.role.teamid', $teamId);

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save role data.
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app    = JFactory::getApplication();
		$roleId = $this->input->getInt('id');
		$teamId = (int) ($roleId) ? $this->getModel()->getItem($roleId)->team : $app->getUserState('com_volunteers.edit.role.teamid');
		$acl    = VolunteersHelper::acl('team', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Reset team
		$app->setUserState('com_volunteers.edit.role.teamid', null);

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Redirect to the team
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_ROLE_SAVED'));
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#roles', false));

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
		$app    = JFactory::getApplication();
		$teamId = $app->getUserState('com_volunteers.edit.role.teamid');

		// Use parent save method
		$return = parent::cancel($key);

		JFactory::getApplication()->setUserState('com_volunteers.edit.report.teamid', null);
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#roles', false));

		return $return;
	}
}