<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Volunteer controller class.
 */
class VolunteersControllerVolunteer extends JControllerForm
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
	 * Method to edit volunteer data
	 *
	 * @return  boolean
	 */
	public function edit($key = null, $urlVar = null)
	{
		// Get variables
		$volunteerId     = $this->input->getInt('id');
		$volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;
		$userId          = (int) JFactory::getUser()->get('id');

		// Check if the volunteer is editing own data
		if ($volunteerUserId != $userId)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $volunteerId));
		}

		// Get the model.
		$model = $this->getModel('Volunteer', 'VolunteersModel');
		$model->checkin();

		// Use parent edit method
		return parent::edit($key, $urlVar);
	}

	/**
	 * Method to save volunteer data.
	 *
	 * @return  boolean
	 */
	public function save($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$volunteerId     = $this->input->getInt('id');
		$volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;
		$userId          = (int) JFactory::getUser()->get('id');

		// Check if the volunteer is saving own data
		if ($volunteerUserId != $userId)
		{
			return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $volunteerId));
		}

		// Use parent save method
		$return = parent::save($key, $urlVar);

		// Remove session variable
		JFactory::getSession()->set('updateprofile', 0);

		// Redirect to the list screen.
		$this->setMessage(JText::_('COM_VOLUNTEERS_LBL_VOLUNTEER_SAVED'));
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));

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
		$volunteerId = $this->input->getInt('id');

		// Use parent save method
		$return = parent::cancel($key);

		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));

		return $return;
	}

	/**
	 * Method to send an email to a volunteer.
	 *
	 * @return  void
	 */
	public function sendMail()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Get variables
		$app         = JFactory::getApplication();
		$user        = JFactory::getUser();
		$session     = JFactory::getSession();
		$volunteerId = $session->get('volunteer');
		$subject     = $this->input->getString('subject', '');
		$message     = $this->input->getString('message', '');

		// Get Volunteer Profile owner
		$volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;
		$volunteer       = JUser::getInstance($volunteerUserId);

		// Get a reference to the Joomla! mailer object
		$mailer = JFactory::getMailer();

		// Set the sender
		$mailer->addReplyTo($user->email, $user->name);

		// Set the recipient
		$mailer->addRecipient($volunteer->email, $volunteer->name);

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

		$app->redirect(JRoute::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));
	}
}