<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Teams list controller class.
 *
 * @since 4.0.0
 */
class TeamsController extends AdminController
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
    public function getModel($name = 'Team', $prefix = 'Administrator', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }
}
