<?php

/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\VolunteersStory\Site\Helper;

use Joomla\CMS\Application\SiteApplication;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

class VolunteersStoryHelper
{
    public function getStory(Registry $params, SiteApplication $app): ?object
    {
        $model = $app->bootComponent('com_volunteers')
            ->getMVCFactory()
            ->createModel('Volunteers', 'Site', ['ignore_request' => true]);

        $model->setState('list.limit', 1);
        $model->setState('list.ordering', 'rand()');
        $model->setState('filter.image', 1);
        $model->setState('filter.joomlastory', 1);
        $model->setState('filter.private', 0);

        $items = $model->getItems();

        return $items[0] ?? null;
    }
}
