<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Contact controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class VolunteersControllerContact extends JControllerForm
{
	/**
	 * Send an email to all active volunteers
	 *
	 * @throws  Exception
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function send()
	{
		$app   = JFactory::getApplication();
		$input = $app->input;

		$canDo = JHelperContent::getActions('com_volunteers');

		if (!$canDo->get('core.manage'))
		{
			throw new Exception('No access to mail sending', 403);
		}

		$mailData = $input->get('jform', array(), 'Array');

		/** @var VolunteersModelContact $model */
		$model = $this->getModel();

		$volunteers = $model->getActiveVolunteers();

		$mailer = JFactory::getMailer();

		$from     = $app->get('mailfrom');
		$fromName = $app->get('fromname');

		$subject = trim($mailData['subject']);
		$body    = trim($mailData['message']);

		foreach ($volunteers as $volunteer)
		{
			if (empty($volunteer->email))
			{
				continue;
			}

			$email = $volunteer->email;

			// This is faster than always recreating the mailer instance
			$mailer->clearAllRecipients();

			$success = $mailer->sendMail($from, $fromName, $email, $subject, $body);

			if (!$success)
			{
				$app->enqueueMessage(JText::sprintf('COM_VOLUNTEERS_MESSAGE_SENDING_FAILED', $email), 'warning');
			}
		}

		$this->setRedirect('index.php?option=com_volunteers&view=contact', JText::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'));
	}
}
