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
$document = JFactory::getDocument();
$input = JFactory::getApplication()->input;

// Set the default view name and format from the Request.
$id      = $input->getInt('id');
$vName   = $input->getCmd('view');
$vFormat = $document->getType();
$lName   = $input->getCmd('layout', 'default');

// Check for edit department form.
if ($vName == 'department' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.department', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

// Check for edit member form.
if ($vName == 'member' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.member', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

// Check for edit report form.
if ($vName == 'report' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.report', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

// Check for edit role form.
if ($vName == 'role' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.role', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

// Check for edit team form.
if ($vName == 'team' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.team', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

// Check for edit volunteer form.
if ($vName == 'volunteer' && $lName == 'edit' && !$controller->checkEditId('com_volunteers.edit.volunteer', $id))
{
	// Somehow the person just went to the form - we don't allow that.
	return JError::raiseError(403, JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
}

$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
