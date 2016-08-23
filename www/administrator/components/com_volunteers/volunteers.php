<?php
/**
 * @package    Joomla! Volunteers
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::_('behavior.tabstate');

if (!JFactory::getUser()->authorise('core.manage', 'com_volunteers'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

JLoader::register('JHtmlVolunteers', JPATH_ADMINISTRATOR . '/components/com_volunteers/helpers/html/volunteers.php');

$controller = JControllerLegacy::getInstance('Volunteers');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
