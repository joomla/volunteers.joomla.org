<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

if (version_compare(JVERSION, '4.0.0', '<')) {
    JHtml::_('behavior.tabstate');
}

if (!Factory::getUser()->authorise('core.manage', 'com_volunteers'))
{
	throw new JAccessExceptionNotallowed(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

JLoader::register('JHtmlVolunteers', JPATH_ADMINISTRATOR . '/components/com_volunteers/helpers/html/volunteers.php');

$controller = JControllerLegacy::getInstance('Volunteers');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
