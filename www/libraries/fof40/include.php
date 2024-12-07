<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Do not put the JEXEC or die check on this file (necessary omission for testing)

use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;

if (!class_exists('FOF40\\Autoloader\\Autoloader'))
{
	// Register utility functions
	require_once __DIR__ . '/Utils/helpers.php';
	// Register the FOF autoloader
	require_once __DIR__ . '/Autoloader/Autoloader.php';
}

if (!defined('FOF40_INCLUDED'))
{
	define('FOF40_INCLUDED', '4.1.4');

	JoomlaFactory::getLanguage()->load('lib_fof40', JPATH_SITE, 'en-GB', true);
	JoomlaFactory::getLanguage()->load('lib_fof40', JPATH_SITE, null, true);

	// Register a debug log
	if (defined('JDEBUG') && JDEBUG && class_exists('\Joomla\CMS\Log\Log'))
	{
		Log::addLogger(array('text_file' => 'fof.log.php'), Log::ALL, array('fof'));
	}
}
