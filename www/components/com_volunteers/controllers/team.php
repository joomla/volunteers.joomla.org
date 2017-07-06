<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Team controller class.
 */
class VolunteersControllerTeam extends JControllerForm
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
	 * Method to add team
	 *
	 * @return  boolean
	 */
	public function add($key = null, $urlVar = null)
	{
		// Get variables
		$department = $this->input->getInt('department');
		$team       = $this->input->getInt('team');

		if ($department)
		{
			$departmentId = $department;
			$teamId       = null;
			$acl          = VolunteersHelper::acl('department', $departmentId);
		}

		if ($team)
		{
			$teamId       = $team;
			$departmentId = $this->getModel()->getItem($teamId)->department;
			$acl          = VolunteersHelper::acl('team', $teamId);
		}

		JFactory::getApplication()->setUserState('com_volunteers.edit.team.departmentid', $departmentId);
		JFactory::getApplication()->setUserState('com_volunteers.edit.team.teamid', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->create_team)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Use parent add method
		return parent::add();
	}

	/**
	 * Method to edit team data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$teamId = $this->input->getInt('id');
		$acl    = VolunteersHelper::acl('team', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save team data
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app    = JFactory::getApplication();
		$teamId = $this->input->getInt('id');
		$team   = $this->getModel()->getItem($teamId);
		$teamId = (int) ($teamId) ? $team->id : $app->getUserState('com_volunteers.edit.team.teamid');

		JFactory::getApplication()->setUserState('com_volunteers.edit.member.teamid', null);
		$acl = VolunteersHelper::acl('team', $teamId);

		// Check if the user is authorized to edit this team
		if (!$acl->edit && $teamId)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId));
		}

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Redirect to the team
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_TEAM_SAVED'));
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));

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
		$teamId = $this->input->getInt('id');
		$team   = $this->getModel()->getItem($teamId);
		$teamId = (int) ($teamId) ? $team->id : $app->getUserState('com_volunteers.edit.team.teamid');

		// Use parent save method
		$return = parent::cancel($key);

		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));

		return $return;
	}

	/**
	 * Method to send an email to team.
	 *
	 * @return  void
	 */
	public function sendMail()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app     = JFactory::getApplication();
		$user    = JFactory::getUser();
		$session = JFactory::getSession();
		$teamId  = $session->get('team');
		$subject = $this->input->getString('subject', '');
		$message = $this->input->getString('message', '');

		// Get team
		$team = $this->getModel()->getItem($teamId);

		// Prefix the subject with the team name for easier identification where this comes from
		$subject = '[' . $team->title . '] ' . $subject;

		// Fallback for missing team email
		if (empty($team->email))
		{
			// Get lead
			$lead = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
			$lead->setState('filter.team', $teamId);
			$lead->setState('filter.position', array(2, 3, 5, 6));
			$lead->setState('filter.active', 1);
			$lead->setState('list.limit', 1);
			$lead->setState('list.ordering', 'position');
			$lead->setState('list.direction', 'asc');
			$lead = $lead->getItems();

			$team->email = $lead[0]->user_email;
		}

		// Get a reference to the Joomla! mailer object
		$mailer = JFactory::getMailer();

		// Set the sender
		$mailer->addReplyTo($user->email, $user->name);

		// Set the recipient
		$mailer->addRecipient($team->email, $team->title);

		// Set the subject
		$mailer->setSubject($subject);

		// Set the body
		$mailer->setBody($message);

		// Send the email
		$send = $mailer->Send();

		// Handle the message
		if ($send == true)
		{
			$app->enqueueMessage(JText::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'), 'message');
		}
		else
		{
			$app->enqueueMessage(JText::_('JERROR_SENDING_EMAIL'), 'warning');
		}

		$app->redirect(JRoute::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
	}
}
