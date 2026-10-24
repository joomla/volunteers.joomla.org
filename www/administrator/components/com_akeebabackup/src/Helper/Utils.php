<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

use Akeeba\Engine\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\Filter\InputFilter;

class Utils
{
	/**
	 * Returns the absolute filesystem path to the log file of a backup run.
	 *
	 * The backup engine normally creates akeeba.<tag>.log.php files. On hosts which do not let us write to files with
	 * a .php extension it falls back to akeeba.<tag>.php and, in older versions, to the web accessible akeeba.<tag>.log
	 * file. This method returns whichever of them exists.
	 *
	 * @param   string|null  $tag  The backup run's tag
	 *
	 * @return  string|null  The absolute path to the log file. NULL if no log file exists.
	 */
	public static function getLogFilePath(?string $tag): ?string
	{
		foreach (Factory::getLog()->getAllLogFilenames($tag) as $logFile)
		{
			if (@is_file($logFile))
			{
				return $logFile;
			}
		}

		return null;
	}

	/**
	 * Returns the relative path of directory $to to root path $from
	 *
	 * Both arguments are treated as directory paths. The presentation of the result follows two rules:
	 *
	 * - it starts with `./` when $to lies inside $from;
	 * - it ends with `/` when $to is either an existing directory or was written with a trailing slash.
	 *
	 * Neither rule affects the number of `..` segments — see the comment on is_dir() below.
	 *
	 * @param   string  $from  Root directory
	 * @param   string  $to    The directory whose path we want to find relative to $from
	 *
	 * @return  string  The relative path
	 */
	public static function getRelativePath(string $from, string $to): string
	{
		// Some compatibility fixes for Windows paths
		$from = str_replace('\\', '/', $from);
		$to   = str_replace('\\', '/', $to);

		/**
		 * The path arithmetic below must not depend on whether either path exists.
		 *
		 * The previous implementation counted the `..` segments it needed from a trailing slash which
		 * was only ever appended when is_dir() returned true. Whenever is_dir() returned false the
		 * count came out one short, and a directory which is merely a sibling of $from was reported as
		 * being inside it — `./backups` rather than `../backups`, pointing the reader at a directory
		 * which does not exist. Walking off the end of the $to array also raised "Undefined array key"
		 * warnings.
		 *
		 * That is not the exotic case it looks like. is_dir() returns false both for a backup output
		 * directory which has since been moved or deleted, and for one which exists but sits outside
		 * open_basedir — the normal situation on shared hosting once the output directory has been
		 * moved above the site root, which is exactly what we recommend people do.
		 *
		 * is_dir() is therefore consulted for presentation only, never for the arithmetic.
		 */
		$toWantsATrailingSlash = is_dir($to) || substr($to, -1) === '/';

		$fromParts = explode('/', rtrim($from, '/'));
		$toParts   = explode('/', rtrim($to, '/'));

		// Discard the common ancestry of the two paths
		while ($fromParts && $toParts && $fromParts[0] === $toParts[0])
		{
			array_shift($fromParts);
			array_shift($toParts);
		}

		// The two paths describe the same directory
		if (!$fromParts && !$toParts)
		{
			return '';
		}

		// One `..` for every directory we have to climb out of $from
		$traversal = str_repeat('../', count($fromParts));

		// $to is an ancestor of $from. The traversal is the whole answer, and it already ends in a slash.
		if (!$toParts)
		{
			return $traversal;
		}

		$descent = implode('/', $toParts);

		// $to lives inside $from. Expressed as ./something; the Manage view strips that prefix back off.
		$relativePath = ($traversal === '') ? './' . $descent : $traversal . $descent;

		return $toWantsATrailingSlash ? $relativePath . '/' : $relativePath;
	}

	/**
	 * Escapes a string for use with Javascript
	 *
	 * @param   string  $string  The string to escape
	 * @param   string  $extras  The characters to escape
	 *
	 * @return  string
	 */
	static function escapeJS(string $string, string $extras = ''): string
	{
		// Make sure we escape single quotes, slashes and brackets
		if (empty($extras))
		{
			$extras = "'\\[]";
		}

		return addcslashes($string, $extras);
	}

	/**
	 * Safely decode a return URL, used in the Backup view.
	 *
	 * Return URLs can have two sources:
	 * - The Backup on Update plugin. In this case the URL is base sixty four encoded and we need to decode it first.
	 * - A custom backend menu item. In this case the URL is a simple string which does not need decoding.
	 *
	 * Further to that, we have to make a few security checks:
	 * - The URL must be internal, i.e. starts with our site's base URL or index.php (this check is executed by Joomla)
	 * - It must not contain single quotes, double quotes, lower than or greater than signs (could be used to execute
	 *   arbitrary JavaScript).
	 *
	 * If any of these violations is detected we return an empty string.
	 *
	 * @param   null|string  $returnUrl
	 *
	 * @return  string
	 */
	static function safeDecodeReturnUrl(?string $returnUrl): string
	{
		// Nulls and non-strings are not allowed
		if (is_null($returnUrl) || !is_string($returnUrl))
		{
			return '';
		}

		// Make sure it's not an empty string
		$returnUrl = trim($returnUrl);

		if (empty($returnUrl))
		{
			return '';
		}

		// Decode a base sixty four encoded string.
		$filter  = new InputFilter();
		$encoded = $filter->clean($returnUrl, 'base64');

		if (($returnUrl == $encoded) && (strpos($returnUrl, 'index.php') === false))
		{
			$possibleReturnUrl = base64_decode($returnUrl);

			if ($possibleReturnUrl !== false)
			{
				$returnUrl = $possibleReturnUrl;
			}
		}

		$checkUrl = $returnUrl;
		$basePath = Uri::base(true);
		$baseUrl = Uri::base(false);

		if (strpos($returnUrl, $basePath) === 0)
		{
			$checkUrl = substr($returnUrl, strlen($basePath));
		}
		elseif (strpos($returnUrl, $baseUrl) === 0)
		{
			$checkUrl = substr($returnUrl, strlen($baseUrl));
		}

		$checkUrl = ltrim($checkUrl, '/');

		// Check if it's an internal URL
		if (!Uri::isInternal($checkUrl))
		{
			return '';
		}

		$disallowedCharacters = ['"', "'", '>', '<'];

		foreach ($disallowedCharacters as $check)
		{
			if (strpos($returnUrl, $check) !== false)
			{
				return '';
			}
		}

		return $returnUrl;
	}
}