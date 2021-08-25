<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_login
 *
 * @copyright   (C) 2005 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$input = JFactory::getApplication()->input;
$task = $input->get('task');

if ($task != 'login' && $task != 'logout')
{
	$input->set('task', '');
	$task = '';
}

$controller = JControllerLegacy::getInstance('Login');
$controller->execute($task);
$controller->redirect();
