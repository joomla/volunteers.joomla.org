<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Volunteers helper
require_once JPATH_ROOT . '/components/com_volunteers/helpers/volunteers.php';

JFactory::getDocument()->addStyleSheet(JURI::root(true) . '/media/com_volunteers/css/frontend.css?v=20170315');

// Load the languages files
$jlang = JFactory::getLanguage();
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, null, true);
$jlang->load('com_users', JPATH_SITE, 'en-GB', true);
$jlang->load('com_users', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_users', JPATH_SITE, null, true);

$controller = JControllerLegacy::getInstance('Volunteers');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
