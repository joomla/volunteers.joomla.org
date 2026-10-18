<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\Component\Volunteers\Administrator\Helper\VolunteersHelper;

/**
 * Roles list controller class.
 *
 * @since 4.0.0
 */
class RolesController extends AdminController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    Optional. Model name
     * @param   string  $prefix  Optional. Class prefix
     * @param   array   $config  Optional. Configuration array for model
     *
     * @return  object  The Model
     *
     * @since   4.0.0
     */
    public function getModel($name = 'Role', $prefix = 'Administrator', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }


    /**
     * Return Team Roles
     *
     *
     * @throws Exception
     *
     * @since 4.0.0
     */
    public function getTeamRoles()
    {
        // Get team ID from input
        $input       = $this->app->input;
        $team        = $input->getInt('team', 0);
        $currentrole = $input->getInt('role', 0);

        // Get the team roles
        $roles = VolunteersHelper::roles($team);

        // Generate option list
        $options   = [];
        $options[] = HTMLHelper::_('select.option', '', Text::_('COM_VOLUNTEERS_SELECT_ROLE'));
        foreach ($roles as $role) {
            $options[] = HTMLHelper::_('select.option', $role->value, $role->text);
        }

        // Echo the options
        echo HTMLHelper::_('select.options', $options, 'value', 'text', $currentrole, true);

        // Bye
        $this->app->close();
        return true;
    }
}
