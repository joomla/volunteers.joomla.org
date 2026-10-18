<?php

/**
 * @package     Joomla.API
 * @subpackage  com_volunteers
 *
 * @copyright   Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

/**
 * The team controller
 *
 * @since  6.1.0
 */
class TeamsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $contentType = 'teams';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  6.1.0
     */
    protected $default_view = 'teams';
}
