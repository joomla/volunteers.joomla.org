<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Helper;

defined('_JEXEC') || die();

class ExceptionChain
{
	/**
	 * Walks a Throwable's chain of previous exceptions, from $e itself to the root cause.
	 *
	 * A hand-rolled `while` loop that advances from the outer exception (`$e->getPrevious()`) instead of the
	 * current one (`$current->getPrevious()`) never terminates once a previous exception exists, because the same
	 * non-null value is assigned on every iteration. This also guards against a pathological chain that somehow
	 * cycles back on itself, which would otherwise loop forever even with the advance fixed.
	 *
	 * @param   \Throwable  $e  The exception to start walking from.
	 *
	 * @return  \Throwable[]  The chain, starting with $e, in outer-to-innermost order.
	 */
	public static function walk(\Throwable $e): array
	{
		$chain = [];
		$seen  = [];

		while ($e !== null)
		{
			$id = spl_object_id($e);

			if (isset($seen[$id]))
			{
				break;
			}

			$seen[$id] = true;
			$chain[]   = $e;
			$e         = $e->getPrevious();
		}

		return $chain;
	}
}
