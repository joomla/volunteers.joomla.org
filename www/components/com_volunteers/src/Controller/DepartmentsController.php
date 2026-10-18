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


use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Departments class.
 *
 * @since  4.0.0
 */
class DepartmentsController extends BaseController
{
    /**
     * Proxy for getModel.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional
     * @param   array   $config  Configuration array for model. Optional
     *
     * @return  object  The model
     *
     * @since   4.0.0
     */
    public function getModel($name = 'Departments', $prefix = 'Site', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }
}
