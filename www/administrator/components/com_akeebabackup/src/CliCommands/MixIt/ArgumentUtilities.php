<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\CliCommands\MixIt;

defined('_JEXEC') || die;

/**
 * Utility methods to manage command arguments
 *
 * @since   7.5.0
 */
trait ArgumentUtilities
{
	/**
	 * Parse the overrides provided in the command line.
	 *
	 * Input: "key1=value1, key2= value2, key3 = value3"
	 * Output: ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3']
	 *
	 * @param   string  $rawString  The raw string
	 *
	 * @return  array  The parsed overrides
	 *
	 * @since   7.5.0
	 */
	private function commaListToMap(string $rawString): array
	{
		if (empty($rawString) || (trim($rawString) == ''))
		{
			return [];
		}

		$rawString = trim($rawString);
		$ret       = [];
		$lines     = explode(',', $rawString);

		foreach ($lines as $line)
		{
			if (strpos($line, '=') === false)
			{
				continue;
			}

			[$key, $value] = explode('=', $line);
			$key       = trim($key);
			$value     = trim($value);
			$ret[$key] = $value;
		}

		return $ret;
	}

	/**
	 * Normalises a requested sort direction to 'asc' or 'desc'.
	 *
	 * @param   string  $requestedOrder  The requested sort direction, already lower-cased.
	 * @param   string  $default         The direction to fall back to when the requested one is not recognised.
	 *
	 * @return  string  Either 'asc' or 'desc'.
	 *
	 * @since   9.9.1
	 */
	private function normalizeSortOrder(string $requestedOrder, string $default = 'desc'): string
	{
		return in_array($requestedOrder, ['asc', 'desc']) ? $requestedOrder : $default;
	}
}
