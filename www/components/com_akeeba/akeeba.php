<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

JDEBUG ? (defined('AKEEBADEBUG') || define('AKEEBADEBUG', 1)) : null;

if (version_compare(PHP_VERSION, '7.2.0', 'lt'))
{
	die('PHP 7.2 or later is required.');
}

if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
{
	throw new RuntimeException('This extension requires FOF 4.', 500);
}

$caCertPath = class_exists('\\Composer\\CaBundle\\CaBundle')
	? \Composer\CaBundle\CaBundle::getBundledCaBundlePath()
	: JPATH_LIBRARIES . '/src/Http/Transport/cacert.pem';

define('AKEEBA_CACERT_PEM', $caCertPath);

FOF40\Container\Container::getInstance('com_akeeba')->dispatcher->dispatch();
