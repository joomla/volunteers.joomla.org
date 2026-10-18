<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\VolunteersLatest\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class VolunteersLatestHelper
{
    public function getVolunteers(Registry $params, SiteApplication $app): array
    {

        $model = $app->bootComponent('com_volunteers')
            ->getMVCFactory()
            ->createModel('Volunteers', 'Site', ['ignore_request' => true]);

        $model->setState('list.limit', (int) $params->get('count', 5));
        $model->setState('list.ordering', 'user.registerDate');
        $model->setState('list.direction', 'desc');
        $model->setState('filter.image', 1);
        $model->setState('filter.private', 0);

        $items = $model->getItems();
        return $items ?? null;
    }
}
