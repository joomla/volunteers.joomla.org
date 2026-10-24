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

namespace Akeeba\Engine\Util;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;

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
 * A filesystem scanner, for internal use
 */
class FileLister
{
	public function &getFiles($folder, $fullpath = false)
	{
		// Initialize variables
		$arr   = [];
		$false = false;

		if (!is_dir($folder) && !is_dir($folder . '/'))
		{
			return $false;
		}

		$handle = @opendir($folder);
		if ($handle === false)
		{
			$handle = @opendir($folder . '/');
		}
		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			return $false;
		}

		$registry            = Factory::getConfiguration();
		$dereferencesymlinks = $registry->get('engine.archiver.common.dereference_symlinks');

		while ((($file = @readdir($handle)) !== false))
		{
			if (($file != '.') && ($file != '..'))
			{
				// # Fix 2.4.b1: Do not add DS if we are on the site's root and it's an empty string
				// # Fix 2.4.b2: Do not add DS is the last character _is_ DS
				$ds     = ($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR;
				$dir    = "$folder/$file";
				$isDir  = @is_dir($dir);
				$isLink = @is_link($dir);

				//if (!$isDir || ($isDir && $isLink && !$dereferencesymlinks) ) {
				if (!$isDir)
				{
					if ($fullpath)
					{
						$data = _AKEEBA_IS_WINDOWS ? Factory::getFilesystemTools()->TranslateWinPath($dir) : $dir;
					}
					else
					{
						$data = _AKEEBA_IS_WINDOWS ? Factory::getFilesystemTools()->TranslateWinPath($file) : $file;
					}
					if ($data)
					{
						$arr[] = $data;
					}
				}
			}
		}
		@closedir($handle);

		return $arr;
	}

	public function &getFolders($folder, $fullpath = false)
	{
		// Initialize variables
		$arr   = [];
		$false = false;

		if (!is_dir($folder) && !is_dir($folder . '/'))
		{
			return $false;
		}

		$handle = @opendir($folder);
		if ($handle === false)

		{
			$handle = @opendir($folder . '/');
		}

		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			return $false;
		}

		$registry            = Factory::getConfiguration();
		$dereferencesymlinks = $registry->get('engine.archiver.common.dereference_symlinks');

		while ((($file = @readdir($handle)) !== false))
		{
			if (($file != '.') && ($file != '..'))
			{
				$dir    = "$folder/$file";
				$isDir  = @is_dir($dir);
				$isLink = @is_link($dir);

				if ($isDir)
				{
					//if(!$dereferencesymlinks && $isLink) continue;
					if ($fullpath)
					{
						$data = _AKEEBA_IS_WINDOWS ? Factory::getFilesystemTools()->TranslateWinPath($dir) : $dir;
					}
					else
					{
						$data = _AKEEBA_IS_WINDOWS ? Factory::getFilesystemTools()->TranslateWinPath($file) : $file;
					}

					if ($data)
					{
						$arr[] = $data;
					}
				}
			}
		}
		@closedir($handle);

		return $arr;
	}
}
