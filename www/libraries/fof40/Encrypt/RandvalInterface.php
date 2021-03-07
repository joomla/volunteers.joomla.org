<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt;

defined('_JEXEC') || die();

interface RandvalInterface
{
	/**
	 * Returns a cryptographically secure random value.
	 *
	 * @param int $bytes How many random bytes do you want to be returned?
	 *
	 * @return string
	 */
	public function generate(int $bytes = 32): string;
}
