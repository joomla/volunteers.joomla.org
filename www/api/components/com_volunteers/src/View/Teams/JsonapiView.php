<?php

/**
 * @package     Joomla.API
 * @subpackage  com_volunteers
 *
 * @copyright   Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Api\View\Teams;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * The teams view
 *
 * @since  6.1.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * The fields to render item in the documents
     *
     * @var  array
     * @since  6.1.0
     */
    protected $fieldsToRenderItem = [
        'id',
        'parent_id',
        'title',
        'alias',
        'department',
        'email',
        'website',
        'state',
        'created',
        'modified',
    ];

    /**
     * The fields to render items in the documents
     *
     * @var  array
     * @since  6.1.0
     */
    protected $fieldsToRenderList = [
        'id',
        'parent_id',
        'title',
        'alias',
        'department',
        'state',
    ];
}
