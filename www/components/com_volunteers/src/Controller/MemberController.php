<?php

/**
 * @package    Com_Volunteers
 * @version    4.0.0
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Service\AclService;
use stdClass;

/**
 * Member controller class.
 *
 * @since 4.0.0
 */
class MemberController extends FormController
{
    protected $view_list = 'members';

    /**
     * Method to add member
     *
     * @return  boolean  True if the record can be added, false if not.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function add()
    {
        // Get variables
        $departmentId = $this->input->getInt('department');
        $teamId       = $this->input->getInt('team');
        $acl          = new stdClass();

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);

        // Department or team?
        if ($departmentId) {
            $acl = $aclService->getAcl('department', $departmentId);
            $this->app->setUserState('com_volunteers.edit.member.departmentid', $departmentId);
        } elseif ($teamId) {
            $acl = $aclService->getAcl('team', $teamId);
            $this->app->setUserState('com_volunteers.edit.member.teamid', $teamId);
        }

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
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
     * @since 4.0.0
     * @throws Exception
     */
    public function cancel($key = null)
    {
        // Get variables
        $departmentId = $this->app->getUserState('com_volunteers.edit.member.departmentid');
        $teamId       = $this->app->getUserState('com_volunteers.edit.member.teamid');

        // Use parent save method
        $return = parent::cancel($key);

        // Department or team?
        if ($departmentId) {
            $this->app->setUserState('com_volunteers.edit.member.departmentid', null);
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
        } elseif ($teamId) {
            $this->app->setUserState('com_volunteers.edit.member.teamid', null);
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
        }

        return $return;
    }

    /**
     * Method to edit member data
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
        $memberId = $this->input->getInt('id');
        $member   = $this->getModel()->getItem($memberId);
        $acl      = new stdClass();

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);

        // Department or team?
        if ($member->department) {
            $acl = $aclService->getAcl('department', (int) $member->department);
            $this->app->setUserState('com_volunteers.edit.member.departmentid', $member->department);
        } elseif ($member->team) {
            $acl = $aclService->getAcl('team', (int) $member->team);
            $this->app->setUserState('com_volunteers.edit.member.teamid', $member->team);
        }

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId), 403);
        }

        // Use parent edit method
        return parent::edit($key, $urlVar);
    }

    /**
     * Method to save member data.
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
        $memberId     = $this->input->getInt('id');
        $member       = $this->getModel()->getItem($memberId);
        $departmentId = ($memberId) ? $member->department : $this->app->getUserState('com_volunteers.edit.member.departmentid');
        $teamId       = ($memberId) ? $member->team : $this->app->getUserState('com_volunteers.edit.member.teamid');
        $acl          = new stdClass();
        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);

        // Department or team?
        if ($departmentId) {
            $this->app->setUserState('com_volunteers.edit.member.departmentid', null);
            $acl = $aclService->getAcl('department', (int) $departmentId);
        } elseif ($teamId) {
            $this->app->setUserState('com_volunteers.edit.member.teamid', null);
            $acl = $aclService->getAcl('team', (int) $teamId);
        }

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $memberId), 403);
        }

        // Use parent save method
        $return = parent::save($key, $urlVar);

        // Redirect to the team
        $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_MEMBER_SAVED'));

        // Department or team?
        if ($departmentId) {
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=department&id=' . $departmentId, false));
        } elseif ($teamId) {
            $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId, false));
        }

        return $return;
    }
}
