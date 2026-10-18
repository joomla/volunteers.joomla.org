<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Controller;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Volunteers list controller class.
 *
 * @since 4.0.0
 */
class VolunteersController extends AdminController
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
    public function getModel($name = 'Volunteer', $prefix = 'Administrator', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }

    /**
     * Reset spam counter
     *
     * @since 1.0
     * @throws Exception
     */
    public function resetspam()
    {
        // Check for request forgeries.
        $this->checkToken();

        /** @var $model VolunteersModel */
        $model = $this->getModel('volunteers');
        $model->resetSpam();

        $this->setMessage(Text::_('COM_VOLUNTEERS_MESSAGE_RESET_SUCCESS'));
        $this->setRedirect(Route::_('index.php?option=com_volunteers&view=volunteers', false));
    }
}
