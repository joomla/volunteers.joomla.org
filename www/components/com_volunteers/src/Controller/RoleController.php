<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Site\Controller;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Volunteers\Administrator\Service\AclService;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Role controller class.
 *
 * @since 4.0.0
 */
class RoleController extends FormController
{
    /**
     * Method to add role
     *
     * @return  boolean  True if the record can be added, false if not.
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function add()
    {
        // Get variables
        $teamId = $this->input->getInt('team');

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Set team
        $this->app->setUserState('com_volunteers.edit.role.teamid', $teamId);

        // Use parent add method
        return parent::add();
    }

    /**
     * Method to edit role data
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
        $roleId = $this->input->getInt('id');
        $teamId = (int) $this->getModel()->getItem($roleId)->team;

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Set team
        $this->app->setUserState('com_volunteers.edit.role.teamid', $teamId);

        // Use parent edit method
        return parent::edit($key, $urlVar);
    }

    /**
     * Method to edit role data
     *
     * @param   null  $key
     * @param   null  $urlVar
     *
     * @return  boolean
     *
     * @since 4.0.0
     * @throws Exception
     */
    public function delete($key = null, $urlVar = null)
    {
        // Get variables
        $roleId = $this->input->getInt('id');
        $teamId = (int) $this->getModel()->getItem($roleId)->team;

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Delete role
        $model  = $this->getModel();
        $return = $model->delete($roleId);

        // Redirect to the team
        $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_ROLE_DELETED'));
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#roles', false));

        return $return;
    }


    /**
     * Method to save role data.
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
        $roleId = $this->input->getInt('id');
        $teamId = ($roleId) ? $this->getModel()->getItem($roleId)->team : $this->app->getUserState('com_volunteers.edit.role.teamid');

        /** @var AclService $aclService */
        $aclService = $this->app->bootComponent('com_volunteers')->getContainer()->get(AclService::class);
        $acl        = $aclService->getAcl('team', (int) $teamId);

        // Check if the user is authorized to edit this team
        if (!$acl->edit) {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $teamId), 403);
        }

        // Reset team
        $this->app->setUserState('com_volunteers.edit.role.teamid', null);

        // Use parent save method
        $return = parent::save($key, $urlVar);

        // Redirect to the team
        $this->setMessage(Text::_('COM_VOLUNTEERS_LBL_ROLE_SAVED'));
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#roles', false));

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
     * @throws Exception
     */
    public function cancel($key = null)
    {
        // Get variables
        $teamId = $this->app->getUserState('com_volunteers.edit.role.teamid');

        // Use parent save method
        $return = parent::cancel($key);

        $this->app->setUserState('com_volunteers.edit.report.teamid', null);
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=team&id=' . $teamId . '#roles', false));

        return $return;
    }
}
