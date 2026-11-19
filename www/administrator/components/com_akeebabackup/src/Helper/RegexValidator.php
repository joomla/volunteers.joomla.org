<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

class RegexValidator
{
	/**
	 * Is $regex a regular expression PHP can compile, as the engine's RegEx filters will use it?
	 *
	 * A leading `!` is Akeeba's extension to PCRE notation (it negates the result). It is not part of the
	 * expression, so it is stripped before testing; what follows must be a valid regex on its own.
	 *
	 * This only tests whether the expression compiles, by running it against a dummy string. Whether it matches
	 * anything is up to the user.
	 *
	 * @param   string  $regex  The filter, as entered by the user
	 *
	 * @return  bool
	 */
	public static function isValid(string $regex): bool
	{
		if (substr($regex, 0, 1) === '!')
		{
			$regex = substr($regex, 1);
		}

		// A compilation error makes preg_match() return false with PREG_INTERNAL_ERROR (and emit a warning)
		if (@preg_match($regex, 'dummy') !== false)
		{
			return true;
		}

		return preg_last_error() !== PREG_INTERNAL_ERROR;
	}
}
