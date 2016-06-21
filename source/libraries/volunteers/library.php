<?php
/**
 * @package     Sample
 * @subpackage  Library
 *
 * @copyright   Copyright (C) 2013 Roberto Segura. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

// non supported PHP version detection. EJECT! EJECT! EJECT!
if(version_compare(PHP_VERSION, '5.4.0', '<'))
{
	return JError::raise(E_ERROR, 500, 'PHP versions less than 5.4.0 are not supported.<br/><br/>');
}

// Common fields
JFormHelper::addFieldPath(dirname(__FILE__) . '/form/field');

require_once __DIR__ . '/helpers/format.php';
require_once __DIR__ . '/helpers/select.php';
require_once __DIR__ . '/helpers/params.php';

// load admin lang file
$jlang = JFactory::getLanguage();
$jlang->load('com_volunteers.sys', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers.sys', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers.sys', JPATH_ADMINISTRATOR, null, true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_volunteers', JPATH_ADMINISTRATOR, null, true);
