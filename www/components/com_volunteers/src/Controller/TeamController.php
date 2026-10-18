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
use Joomla\Component\Volunteers\Administrator\Model\MembersModel;
use Joomla\Component\Volunteers\Administrator\Service\AclService;
use Joomla\Database\DatabaseInterface;
use stdClass;

/**
 * Team controller class.
 *
 * @since 4.0.0
 */
class TeamController extends FormController
{
    protected $view_list = 'teams';

    /**
     * Method to add team
     *
     * @return  boolean  True if the record can be added, false if not.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function add()
    {
        // Get variables
        $department = $this->input->getInt('department');
        $team       = $this->input->getInt('team');
        $acl        = new stdClass();
        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);

        if ($department) {
            $departmentId = $department;
            $teamId       = null;
            $acl          = $aclService->getAcl('department', $departmentId);
        }

        if ($team) {
            $teamId       = $team;
            $departmentId = $this->getModel()->getItem($teamId)->department;
            $acl          = $aclService->getAcl('team', $teamId);
        }

        $this->app->setUserState('com_volunteers.edit.team.departmentid', $departmentId);
        $this->app->setUserState('com_volunteers.edit.team.teamid', $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->create_team) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Use parent add method
        return parent::add();
    }

    /**
     * Method to cancel member data.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function cancel($key = null)
    {
        // Get variables
        $teamId = $this->input->getInt('id');
        $team   = $this->getModel()->getItem($teamId);
        $teamId = ($teamId) ? $team->id : $this->app->getUserState('com_volunteers.edit.team.teamid');

        // Use parent save method
        $return = parent::cancel($key);

        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));

        return $return;
    }

    /**
     * Method to edit team data
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key
     *                           (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if access level check and checkout passes, false otherwise.
     * @since 4.0.0
     * @throws Exception
     */
    public function edit($key = null, $urlVar = null)
    {
        // Get variables
        $teamId = $this->input->getInt('id');

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Use parent edit method
        return parent::edit($key, $urlVar);
    }

    /**
     * Method to save team data
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $teamId = $this->input->getInt('id');
        $team   = $this->getModel()->getItem($teamId);
        $teamId = ($teamId) ? $team->id : $this->app->getUserState('com_volunteers.edit.team.teamid');

        $this->app->setUserState('com_volunteers.edit.member.teamid', null);

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', (int) $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit && $teamId) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Use parent save method
        $return = parent::save($key, $urlVar);

        // Redirect to the team
        $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_TEAM_SAVED'));
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));

        return $return;
    }

    /**
     * Method to send an email to team.
     *
     * @return  void
     * @since 4.0.0
     * @throws Exception
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendMail()
    {
        // Check for request forgeries.
        $this->checkToken();

        // Get variables
        $session = $this->app->getSession();
        $user    = $this->app->getIdentity();
        $teamId  = $session->get('team');
        $subject = $this->input->getString('subject', '');
        $message = $this->input->getString('message', '');

        // Get team
        $team = $this->getModel()->getItem($teamId);

        // Prefix the subject with the team name for easier identification where this comes from
        $subject = '[' . $team->title . '] ' . $subject;

        // Fallback for missing team email
        if (empty($team->email)) {
            // Get lead
            /** @var MembersModel $lead */
            $lead = $this->createModel('Members', 'Administrator', ['ignore_request' => true]);
            $lead->setState('filter.team', $teamId);
            $lead->setState('filter.position', [2, 3, 5, 6]);
            $lead->setState('filter.active', 1);
            $lead->setState('list.limit', 1);
            $lead->setState('list.ordering', 'position');
            $lead->setState('list.direction', 'asc');
            $lead = $lead->getItems();

            $team->email = $lead[0]->user_email;
        }

        // Get email department coordinator for CC
        $db    = $this->app->getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();
        $query
            ->select($db->quoteName(['user.email', 'user.name']))
            ->from($db->quoteName('#__volunteers_members', 'member'))
            ->join('LEFT', $db->quoteName('#__volunteers_volunteers', 'volunteer') . ' ON ' . $db->qn('member.volunteer') . ' = ' . $db->qn('volunteer.id'))
            ->join('LEFT', $db->quoteName('#__users', 'user') . ' ON ' . $db->qn('volunteer.user_id') . ' = ' . $db->qn('user.id'))
            ->where($db->quoteName('member.department') . ' = ' . (int) $team->department)
            ->where($db->quoteName('member.position') . ' = 11')
            ->where($db->quoteName('member.date_ended') . ' IS NULL');

        try {
            $coordinator = $db->setQuery($query)->loadObject();
        } catch (\RuntimeException) {
            $this->app->enqueueMessage(Text::_('JERROR_SENDING_EMAIL'), 'warning');
        }

        // Get a reference to the Joomla! mailer object
        $mailer = $this->app->getContainer()->get(MailerFactoryInterface::class)->createMailer();

        // Set the sender
        $mailer->addReplyTo($user->email, $user->name);

        // Set the recipient
        $mailer->addRecipient($team->email, $team->title);
        $mailer->addCc($coordinator->email, $coordinator->name);

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

        $this->app->redirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
    }
}
