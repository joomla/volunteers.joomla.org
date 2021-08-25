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

if (!require_once(__DIR__ . '/tmpl/CommonTemplates/wrongphp.php'))
{
	return;
}

// HHVM made sense in 2013, now PHP 7 is a way better solution than a hybrid PHP interpreter
if (defined('HHVM_VERSION'))
{
	(include_once __DIR__ . '/tmpl/CommonTemplates/hhvm.php') || die('We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please switch to PHP 7 instead.');

	return;
}

// So, FEF is not installed?
if (!@file_exists(JPATH_SITE . '/media/fef/fef.php'))
{
	(include_once __DIR__ . '/tmpl/CommonTemplates/fef.php') || die('You need to have the Akeeba Frontend Framework (FEF) package installed on your site to display this component. Please visit https://www.akeeba.com/download/official/fef.html to download it and install it on your site.');

	return;
}

/**
 * The following code is a neat trick to help us collect the maximum amount of relevant information when a user
 * encounters an unexpected exception or a PHP fatal error. In both cases we capture the generated Throwable and
 * render an error page, making sure that the HTTP response code is set to an appropriate value (4xx or 5xx).
 */
try
{
	if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
	{
		(include_once __DIR__ . '/tmpl/CommonTemplates/fof.php') || die('You need to have the Akeeba Framework-on-Framework (FOF) 3 package installed on your site to use this component. Please visit https://www.akeeba.com/download/fof3.html to download it and install it on your site.');

		return;
	}

	$caCertPath = class_exists('\\Composer\\CaBundle\\CaBundle')
		? \Composer\CaBundle\CaBundle::getBundledCaBundlePath()
		: JPATH_LIBRARIES . '/src/Http/Transport/cacert.pem';

	define('AKEEBA_CACERT_PEM', $caCertPath);

	FOF40\Container\Container::getInstance('com_akeeba')->dispatcher->dispatch();
}
catch (Throwable $e)
{
	$title = 'Akeeba Backup';
	$isPro = defined(AKEEBA_PRO) ? AKEEBA_PRO : file_exists(__DIR__ . '/tmpl/CommonTemplates/RegExDatabaseFilters/Html.php');

	if (!(include_once __DIR__ . '/tmpl/CommonTemplates/errorhandler.php'))
	{
		throw $e;
	}
}
