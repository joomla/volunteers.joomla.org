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

/**
 * PHP 8.4+ workaround for standalone MD5 and SHA-1 functions.
 *
 * PHP 8.4 deprecates the standalone md5(), md5_file(), sha1(), and sha1_file() functions. This trait creates shims
 * which use the hash() and hash_file() functions instead where available.
 *
 * IMPORTANT! PHP 7.4 made the ext/hash extension mandatory. These shims are here only as a backwards compatibility aid.
 * Eventually, we need to remove them, replacing their use by the direct use of hash() and hash_file().
 *
 * @deprecated 10.0
 */
trait HashTrait
{
	/**
	 * @deprecated 10.0 Use hash() instead
	 */
	private static function md5($string, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('md5', hash_algos());
		}

		return $shouldUseHash ? hash('md5', $string, $binary) : md5($string, $binary);
	}

	/**
	 * @deprecated 10.0 Use hash() instead
	 */
	private static function sha1($string, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('sha1', hash_algos());
		}

		return $shouldUseHash ? hash('sha1', $string, $binary) : sha1($string, $binary);
	}

	/**
	 * @deprecated 10.0 Use hash_file() instead
	 */
	private static function md5_file($filename, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('md5', hash_algos());
		}

		return $shouldUseHash ? hash_file('md5', $filename, $binary) : md5_file($filename, $binary);
	}

	/**
	 * @deprecated 10.0 Use hash_file() instead
	 */
			private static function sha1_file($filename, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('sha1', hash_algos());
		}

		return $shouldUseHash ? hash_file('sha1', $filename, $binary) : sha1_file($filename, $binary);
	}
}