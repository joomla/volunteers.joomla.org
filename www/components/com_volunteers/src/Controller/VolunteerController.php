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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Volunteer controller class.
 *
 * @since 4.0.0
 */
class VolunteerController extends FormController
{
    protected $view_list = 'volunteers';

    /**
     * Method to edit volunteer data
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key
     *                           (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if access level check and checkout passes, false otherwise.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function edit($key = null, $urlVar = null)
    {
        // Get variables
        $volunteerId     = $this->input->getInt('id');
        $volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;

        $userId       = $this->app->getIdentity()->id;
        // Check if the volunteer is editing own data
        if ($volunteerUserId != $userId) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $volunteerId), 403);
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
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     * @since 4.0.0
     * @throws Exception
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $volunteerId     = $this->input->getInt('id');
        $volunteerUserId = (int) $this->getModel()->getItem($volunteerId)->user_id;
        $userId          = $this->app->getIdentity()->id;
        // Check if the volunteer is saving own data
        if ($volunteerUserId != $userId) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $volunteerId), 403);
        }

        // Use parent save method
        $return = parent::save($key, $urlVar);

        // Remove session variable
        $this->app->getSession()->set('updateprofile', 0);

        // Redirect to the list screen.
        if ($return == true) {
            $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_VOLUNTEER_SAVED'));
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));
        }

        return $return;
    }

    /**
     * Method to cancel member data.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since 4.0.0
     */
    public function cancel($key = null)
    {
        // Get variables
        $volunteerId = $this->input->getInt('id');

        // Use parent save method
        $return = parent::cancel($key);

        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $volunteerId, false));

        return $return;
    }

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
        $userFactory     = $this->app->getContainer()->get(UserFactoryInterface::class);

        $volunteer =  $userFactory->loadUserById($volunteerUserId);

        // Get a reference to the Joomla! mailer object
        $mailer = $this->app->getContainer()->get(MailerFactoryInterface::class)->createMailer();

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
