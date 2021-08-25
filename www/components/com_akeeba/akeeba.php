<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die();

defined('AKEEBA_COMMON_WRONGPHP') || define('AKEEBA_COMMON_WRONGPHP', 1);

JDEBUG ? (defined('AKEEBADEBUG') || define('AKEEBADEBUG', 1)) : null;

$minPHPVersion         = '7.2.0';
$recommendedPHPVersion = '7.4';
$softwareName          = 'Akeeba Backup';
$silentResults         = true;

if (!require_once(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/CommonTemplates/wrongphp.php'))
{
	die;
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
