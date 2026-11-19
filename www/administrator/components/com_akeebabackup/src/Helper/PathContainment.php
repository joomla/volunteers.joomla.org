<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

class PathContainment
{
	/**
	 * Is $path the same directory as $root, or a filesystem descendant of it?
	 *
	 * Both arguments must already be normalised, absolute paths (e.g. the result of realpath()). A naive
	 * strpos($path, $root) === 0 check is wrong: it also matches sibling directories that merely share $root as a
	 * string prefix, e.g. /var/www/html-backups is NOT inside /var/www/html even though the prefix matches.
	 *
	 * @param   string  $path  The candidate path, already normalised (e.g. via realpath()).
	 * @param   string  $root  The containing directory to test against, already normalised.
	 *
	 * @return  bool  True if $path is $root itself, or a descendant of it.
	 */
	public static function isUnderRoot(string $path, string $root): bool
	{
		$root = rtrim($root, '/\\');

		return ($path === $root) || (strpos($path, $root . DIRECTORY_SEPARATOR) === 0);
	}
}
