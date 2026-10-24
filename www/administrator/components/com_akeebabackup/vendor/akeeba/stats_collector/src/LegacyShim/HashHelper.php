<?php
/*
 * @package   stats_collector
 * @copyright Copyright (c)2023-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\UsageStats\Collector\LegacyShim;

/**
 * PHP 8.4+ workaround for standalone MD5 and SHA-1 functions.
 *
 * PHP 8.4 deprecates the standalone md5(), md5_file(), sha1(), and sha1_file() functions. This trait creates shims
 * which use the hash() and hash_file() functions instead where available.
 *
 * IMPORTANT! PHP 7.4 made the ext/hash extension mandatory. These shims are here only as a backwards compatibility aid.
 * Eventually, we need to remove them, replacing their use by the direct use of hash() and hash_file().
 *
 * @deprecated 2.0
 */
class HashHelper
{
	/**
	 * @deprecated 2.0 Use hash() instead
	 */
	public static function md5($string, $binary = false)
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
	 * @deprecated 2.0 Use hash() instead
	 */
	public static function sha1($string, $binary = false)
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
	 * @deprecated 2.0 Use hash_file() instead
	 */
	public static function md5_file($filename, $binary = false)
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
	 * @deprecated 2.0 Use hash_file() instead
	 */
	public static function sha1_file($filename, $binary = false)
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