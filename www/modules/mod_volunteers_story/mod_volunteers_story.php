<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

$model = JModelLegacy::getInstance('Volunteers', 'VolunteersModel', array('ignore_request' => true));
$model->setState('list.limit', 1);
$model->setState('list.ordering', 'rand()');
$model->setState('filter.image', 1);
$model->setState('filter.joomlastory', 1);

$items = $model->getItems();

$story = $items[0];

require JModuleHelper::getLayoutPath('mod_volunteers_story', 'default');
