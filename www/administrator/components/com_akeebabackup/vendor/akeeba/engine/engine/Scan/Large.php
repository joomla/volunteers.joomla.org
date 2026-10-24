<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Scan;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Base\Exceptions\WarningException;
use Akeeba\Engine\Factory;
use DirectoryIterator;
use Exception;
use RuntimeException;

/* Windows system detection */
if (!defined('_AKEEBA_IS_WINDOWS'))
{
	$isWindows = DIRECTORY_SEPARATOR == '\\';

	if (function_exists('php_uname'))
	{
		$isWindows = stristr(php_uname(), 'windows');
	}

	define('_AKEEBA_IS_WINDOWS', $isWindows);
}

/**
 * A filesystem scanner which uses opendir() and is smart enough to make large directories
 * be scanned inside a step of their own.
 *
 * The idea is that if it's not the first operation of this step and the number of contained
 * directories AND files is more than double the number of allowed files per fragment, we should
 * break the step immediately.
 *
 */
class Large extends Base
{
	public function getFiles($folder, &$position)
	{
		return $this->scanFolder($folder, $position, false, 'file', 100);
	}

	public function getFolders($folder, &$position)
	{
		return $this->scanFolder($folder, $position, true, 'dir', 50);
	}

	protected function scanFolder($folder, &$position, $forFolders = true, $threshold_key = 'dir', $threshold_default = 50)
	{
		$registry = Factory::getConfiguration();

		// Initialize variables
		$arr   = [];
		$false = false;

		if (!is_dir($folder) && !is_dir($folder . '/'))
		{
			throw new WarningException('Cannot list contents of directory ' . $folder . ' -- PHP reports it as not a folder.');
		}

		if (!@is_readable($folder))
		{
			throw new WarningException('Cannot list contents of directory ' . $folder . ' -- PHP reports it as not readable.');
		}

		try
		{
			$di = new DirectoryIterator($folder);
		}
		catch (Exception $e)
		{
			throw new WarningException('Cannot list contents of directory ' . $folder . ' -- PHP\'s DirectoryIterator reports the path cannot be opened.', 0, $e);
		}

		if (!$di->valid())
		{
			throw new WarningException('Cannot list contents of directory ' . $folder . ' -- PHP\'s DirectoryIterator could open the folder but immediately reports itself as not valid. If this happens your server is about to die.');
		}

		if (!empty($position))
		{
			$di->seek($position);

			if ($di->key() != $position)
			{
				$position = null;

				return $arr;
			}
		}

		$counter    = 0;
		$maxCounter = $registry->get("engine.scan.large.{$threshold_key}_threshold", $threshold_default);

		while ($di->valid())
		{
			/**
			 * If the directory entry is a link pointing somewhere outside the allowed directories per open_basedir we
			 * will get a RuntimeException (tested on PHP 5.3 onwards). Catching it lets us report the link as
			 * unreadable without suffering a PHP Fatal Error.
			 */
			try
			{
				$di->isLink();
			}
			catch (RuntimeException $e)
			{
				if (!in_array($di->getFilename(), ['.', '..']))
				{
					Factory::getLog()->warning(sprintf("Link %s is inaccessible. Check the open_basedir restrictions in your server's PHP configuration", $di->getPathname()));
				}

				$di->next();

				continue;
			}

			if ($di->isDot())
			{
				$di->next();

				continue;
			}

			if ($di->isDir() != $forFolders)
			{
				$di->next();

				continue;
			}

			$ds  = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR;
			$dir = $folder . $ds . $di->getFilename();

			$data = _AKEEBA_IS_WINDOWS ? Factory::getFilesystemTools()->TranslateWinPath($dir) : $dir;

			if ($data)
			{
				$counter++;
				$arr[] = $data;
			}

			if ($counter == $maxCounter)
			{
				break;
			}

			$di->next();
		}

		// Determine the new value for the position
		$di->next();

		$position = $di->valid() ? ($di->key() - 1) : null;

		return $arr;
	}
}
