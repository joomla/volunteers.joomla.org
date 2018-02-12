<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Get reports
$model = JModelLegacy::getInstance('Volunteers', 'VolunteersModel', array('ignore_request' => true));
$model->setState('list.limit', 5);
$model->setState('list.ordering', 'a.created');
$model->setState('list.direction', 'desc');
$model->setState('filter.image', 1);

$volunteers = $model->getItems();

require JModuleHelper::getLayoutPath('mod_volunteers_latest', 'default');
