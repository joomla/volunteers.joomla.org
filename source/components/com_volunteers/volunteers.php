<?php
/*
 * @package		Joomla! Volunteers
 * @copyright   Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Load FOF
include_once JPATH_LIBRARIES.'/fof/include.php';
if(!defined('FOF_INCLUDED')) {
	JError::raiseError ('500', 'FOF is not installed');

	return;
}

// Load js
JHtml::_('bootstrap.framework');
JHTML::_('formbehavior.chosen', 'select');

// Load css
$doc = JFactory::getDocument();
$doc->addStyleSheet(JURI::root(true).'/media/com_volunteers/css/frontend.css');


FOFDispatcher::getTmpInstance('com_volunteers')->dispatch();