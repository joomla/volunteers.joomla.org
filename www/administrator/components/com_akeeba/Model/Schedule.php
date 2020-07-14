<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Platform;
use FOF30\Model\Model;

class Schedule extends Model
{
	public function getPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'altcli'   => (object) [
				'supported' => false,
				'path'      => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'json'     => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'   => false,
				'php_path'  => false,
				'root_url'  => false,
				'secret'    => '',
				'jsonapi'   => false,
				'legacyapi' => false,
			],
		];

		$currentProfileID = Platform::getInstance()->get_active_profile();
		$siteRoot         = rtrim(realpath(JPATH_ROOT), DIRECTORY_SEPARATOR);

		$ret->info->windows   = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		$ret->info->php_path  = $ret->info->windows ? 'c:\path\to\php.exe' : '/path/to/php';
		$ret->info->root_url  = rtrim($this->container->params->get('siteurl', ''), '/');
		$ret->info->secret    = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$ret->info->jsonapi   = Platform::getInstance()->get_platform_configuration_option('jsonapi_enabled', '');
		$ret->info->legacyapi = Platform::getInstance()->get_platform_configuration_option('legacyapi_enabled', '');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'akeeba-backup.php']);

		if ($currentProfileID != 1)
		{
			$ret->cli->path .= ' --profile=' . $currentProfileID;
		}

		// Get information for alternative CLI CRON script
		$ret->altcli->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret))
		{
			$ret->altcli->path = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'akeeba-altbackup.php']);

			if ($currentProfileID != 1)
			{
				$ret->altcli->path .= ' --profile=' . $currentProfileID;
			}
		}

		// Get information for front-end backup
		$ret->frontend->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret) && $ret->info->legacyapi)
		{
			$ret->frontend->path = 'index.php?option=com_akeeba&view=Backup&key='
				. urlencode($ret->info->secret);

			if ($currentProfileID != 1)
			{
				$ret->frontend->path .= '&profile=' . $currentProfileID;
			}
		}

		// Get information for JSON API backups
		$ret->json->supported = $ret->info->jsonapi;
		$ret->json->path      = 'index.php?option=com_akeeba&view=Json&format=raw';

		return $ret;
	}

	public function getCheckPaths()
	{
		$ret = (object) [
			'cli'      => (object) [
				'supported' => false,
				'path'      => false,
			],
			'altcli'   => (object) [
				'supported' => false,
				'path'      => false,
			],
			'frontend' => (object) [
				'supported' => false,
				'path'      => false,
			],
			'info'     => (object) [
				'windows'   => false,
				'php_path'  => false,
				'root_url'  => false,
				'secret'    => '',
				'jsonapi'   => false,
				'legacyapi' => false,
			],
		];

		$currentProfileID = Platform::getInstance()->get_active_profile();
		$siteRoot         = rtrim(realpath(JPATH_ROOT), DIRECTORY_SEPARATOR);

		$ret->info->windows   = (DIRECTORY_SEPARATOR == '\\') || (substr(strtoupper(PHP_OS), 0, 3) == 'WIN');
		$ret->info->php_path  = $ret->info->windows ? 'c:\path\to\php.exe' : '/path/to/php';
		$ret->info->root_url  = rtrim($this->container->params->get('siteurl', ''), '/');
		$ret->info->secret    = Platform::getInstance()->get_platform_configuration_option('frontend_secret_word', '');
		$ret->info->jsonapi   = Platform::getInstance()->get_platform_configuration_option('jsonapi_enabled', '');
		$ret->info->legacyapi = Platform::getInstance()->get_platform_configuration_option('legacyapi_enabled', '');

		// Get information for CLI CRON script
		$ret->cli->supported = true;
		$ret->cli->path      = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'akeeba-check-failed.php']);

		if ($currentProfileID != 1)
		{
			$ret->cli->path .= ' --profile=' . $currentProfileID;
		}

		// Get information for alternative CLI CRON script
		$ret->altcli->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret))
		{
			$ret->altcli->path = implode(DIRECTORY_SEPARATOR, [$siteRoot, 'cli', 'akeeba-altcheck-failed.php']);

			if ($currentProfileID != 1)
			{
				$ret->altcli->path .= ' --profile=' . $currentProfileID;
			}
		}

		// Get information for front-end backup
		$ret->frontend->supported = $ret->info->legacyapi;

		if (trim($ret->info->secret) && $ret->info->legacyapi)
		{
			$ret->frontend->path = 'index.php?option=com_akeeba&view=Check&key='
				. urlencode($ret->info->secret);

			if ($currentProfileID != 1)
			{
				$ret->frontend->path .= '&profile=' . $currentProfileID;
			}
		}

		return $ret;
	}
}
