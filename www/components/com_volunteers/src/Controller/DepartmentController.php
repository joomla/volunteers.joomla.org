<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Service\AclService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Department controller class.
 *
 * @since  4.0.0
 */
class DepartmentController extends FormController
{
    protected $view_list = 'departments';

    /**
     * Method to edit department data
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key
     *                           (sometimes required to avoid router collisions).
     *
     * @return  bool  True if access level check and checkout passes, false otherwise.
     * @since 4.0.0
     * @throws Exception
     */
    public function edit($key = null, $urlVar = null): bool
    {
        // Get variables
        $departmentId = $this->input->getInt('id');

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('department', $departmentId);

        // Check if the user is authorized to edit this department
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $departmentId), 403);
        }

        // Use parent edit method
        return parent::edit($key, $urlVar);
    }

    /**
     * Method to save department data
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  bool  True if successful, false otherwise.
     * @since 4.0.0
     * @throws Exception
     */
    public function save($key = null, $urlVar = null): bool
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $departmentId = $this->input->getInt('id');

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('department', $departmentId);

        // Check if the user is authorized to edit this department
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $departmentId), 403);
        }

        // Use parent save method
        $return = parent::save($key, $urlVar);

        // Redirect to the department
        $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_DEPARTMENT_SAVED'));
        if ($departmentId === 58) {
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=board', false));
        } else {
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
        }

        return $return;
    }

    /**
     * Method to cancel member data.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  bool  True if access level checks pass, false otherwise.
     *
     * @since 4.0.0
     */
    public function cancel($key = null)
    {
        // Get variables
        $departmentId = $this->input->getInt('id');

        // Use parent cancel method
        //$return = parent::cancel($key);

        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));

        return true;
    }

    /**
     * Method to send an email to department.
     *
     * @return  void
     * @since 4.0.0
     * @throws Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendMail(): void
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $session      = $this->app->getSession();
        $user         = $this->app->getIdentity();
        $departmentId = $session->get('department');
        $subject      = $this->input->getString('subject', '');
        $message      = $this->input->getString('message', '');

        // Get department
        $department = $this->getModel()->getItem($departmentId);

        // Prefix the subject with the team name for easier identification where this comes from
        $subject = '[' . $department->title . '] ' . $subject;

        // Fallback for missing department email
        if (empty($department->email)) {
            // Get lead

            $lead = $this->createModel('Members', 'Administrator', ['ignore_request' => true]);
            $lead->setState('filter.department', $departmentId);
            $lead->setState('list.limit', 1);
            $lead->setState('list.ordering', 'position');
            $lead->setState('list.direction', 'asc');
            $lead = $lead->getItems();

            $department->email = $lead[0]->user_email;
        }

        // Get a reference to the Joomla! mailer object
        $mailer = $this->app->getContainer()->get(MailerFactoryInterface::class)->createMailer();

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
        if ($send === true) {
            $this->app->enqueueMessage(Text::_('COM_VOLUNTEERS_MESSAGE_SEND_SUCCESS'), 'message');
        } else {
            $this->app->enqueueMessage(Text::_('JERROR_SENDING_EMAIL'), 'warning');
        }

        $this->app->redirect(Route::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
    }
}
