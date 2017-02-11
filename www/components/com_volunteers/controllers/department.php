<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Department controller class.
 */
class VolunteersControllerDepartment extends JControllerForm
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
	 * Method to edit department data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$departmentId = $this->input->getInt('id');
		$acl          = VolunteersHelper::acl('department', $departmentId);

		// Check if the user is authorized to edit this department
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $departmentId));
		}

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save department data
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$departmentId = $this->input->getInt('id');
		$acl          = VolunteersHelper::acl('department', $departmentId);

		// Check if the user is authorized to edit this department
		if (!$acl->edit)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $departmentId));
		}

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Redirect to the department
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_TEAM_SAVED'));
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));

		return $return;
	}

	/**
	 * Method to send an email to department.
	 *
	 * @return  void
	 */
	public function sendMail()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app          = JFactory::getApplication();
		$user         = JFactory::getUser();
		$session      = JFactory::getSession();
		$departmentId = $session->get('department');
		$subject      = $this->input->getString('subject', '');
		$message      = $this->input->getString('message', '');

		// Get department
		$department = $this->getModel()->getItem($departmentId);

		// Prefix the subject with the team name for easier identification where this comes from
		$subject = '[' . $department->title . '] ' . $subject;

		// Fallback for missing department email
		if (empty($department->email))
		{
			// Get lead
			$lead = JModelLegacy::getInstance('Members', 'VolunteersModel', array('ignore_request' => true));
			$lead->setState('filter.department', $departmentId);
			$lead->setState('list.limit', 1);
			$lead->setState('list.ordering', 'position');
			$lead->setState('list.direction', 'asc');
			$lead = $lead->getItems();

			$department->email = $lead[0]->user_email;
		}

		// Get a reference to the Joomla! mailer object
		$mailer = JFactory::getMailer();

		// Set the sender
		$mailer->addReplyTo($user->email, $user->name);

		// Set the recipient
		$mailer->addRecipient($department->email, $department->title);

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

		$app->redirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
	}

	/**
	 * Method to cancel member data.
	 *
	 * @return  boolean
	 */
	public function cancel($key = null)
	{
		// Get variables
		$departmentId = $this->input->getInt('id');

		// Use parent save method
		$return = parent::cancel($key);

		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));

		return $return;
	}
}