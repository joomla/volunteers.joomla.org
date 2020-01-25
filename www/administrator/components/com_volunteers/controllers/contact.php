<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;

// No direct access.
defined('_JEXEC') or die;

/**
 * Contact controller class.
 */
class VolunteersControllerContact extends JControllerForm
{
	/**
	 * Send an email to all active volunteers
	 *
	 * @return  void
	 * @since 1.0.0
	 * @throws  Exception
	 */
	public function send()
	{
		$mailer  = Factory::getMailer();
		$app     = Factory::getApplication();
		$session = Factory::getSession();
		$canDo   = JHelperContent::getActions('com_volunteers');

		// Super users access only
		if (!$canDo->get('core.manage'))
		{
			throw new Exception('No access to mail sending', 403);
		}

		$mailData   = $app->input->get('jform', array(), 'Array');
		$from       = $app->get('mailfrom');
		$fromName   = $app->get('fromname');
		$subject    = trim($mailData['subject']);
		$body       = trim($mailData['message']);
		$recipients = $session->get('volunteers.recipients');

		// Collect the emails for the recipients
		$emails = [];

		foreach ($recipients as $recipient)
		{
			$emails[] = $recipient['email'];
		}

		$emails = array_unique($emails);

		// Send mail
		$success = $mailer->sendMail($from, $fromName, $emails, $subject, $body, true);

		if (!$success)
		{
			$app->enqueueMessage(JText::_('COM_VOLUNTEERS_MESSAGE_SENDING_FAILED'), 'warning');
		}

		// Clear recipients
		$session->clear('volunteers.recipients');

		$this->setRedirect('index.php?option=com_volunteers&view=members', JText::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'));
	}

	/**
	 * Method for closing the contact form.
	 *
	 * @return  void
	 * @since 1.0.0
	 */
	public function cancel($key = null)
	{
		$this->setRedirect(JRoute::_('index.php?option=com_volunteers&view=members', false));
	}
}
