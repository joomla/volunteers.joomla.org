<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Volunteer controller class.
 *
 * Joomlers edit their profile on the Joomla! Identity portal, this controller only sends a message to a Joomler.
 *
 * @since 4.0.0
 */
class VolunteerController extends BaseController
{
    /**
     * Method to send an email to a volunteer.
     *
     * @return  void
     *
     * @since 4.0.0
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function sendMail()
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $session     = $this->app->getSession();
        $user        = $this->app->getIdentity();
        $volunteerId = $session->get('volunteer');
        $subject     = $this->input->getString('subject', '');
        $message     = $this->input->getString('message', '');

        // Get Volunteer Profile owner
        $volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;
        $userFactory     = Factory::getContainer()->get(UserFactoryInterface::class);

        $volunteer =  $userFactory->loadUserById($volunteerUserId);

        // Get a reference to the Joomla! mailer object
        $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

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
        if ($send == true) {
            $this->app->enqueueMessage(Text::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::_('JERROR_SENDING_EMAIL'), 'warning');
        }

        $this->app->redirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));
    }
}
