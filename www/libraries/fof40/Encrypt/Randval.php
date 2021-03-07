<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt;

defined('_JEXEC') || die();

/**
 * Generates cryptographically-secure random values.
 */
class Randval implements RandvalInterface
{
	/**
	 * Returns a cryptographically secure random value.
	 *
	 * Since we only run on PHP 7+ we can use random_bytes(), which internally uses a crypto safe PRNG. If the function
	 * doesn't exist, Joomla already loads a secure polyfill.
	 *
	 * The reason this method exists is backwards compatibility with older versions of FOF. It also allows us to quickly
	 * address any future issues if Joomla drops the polyfill or otherwise find problems with PHP's random_bytes() on
	 * some weird host (you can't be too carefull when releasing mass-distributed software).
	 *
	 * @param   integer  $bytes  How many bytes to return
	 *
	 * @return  string
	 */
	public function generate(int $bytes = 32): string
	{
		return random_bytes($bytes);
	}

	/**
	 * Return a randomly generated password using safe characters (a-z, A-Z, 0-9).
	 *
	 * @param   int  $length  How many characters long should the password be. Default is 64.
	 *
	 * @return  string
	 *
	 * @since   3.3.2
	 */
	public function getRandomPassword($length = 64)
	{
		$salt     = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$base     = strlen($salt);
		$makepass = '';

		/*
		 * Start with a cryptographic strength random string, then convert it to
		 * a string with the numeric base of the salt.
		 * Shift the base conversion on each character so the character
		 * distribution is even, and randomize the start shift so it's not
		 * predictable.
		 */
		$random = $this->generate($length + 1);
		$shift  = ord($random[0]);

		for ($i = 1; $i <= $length; ++$i)
		{
			$makepass .= $salt[($shift + ord($random[$i])) % $base];
			$shift    += ord($random[$i]);
		}

		return $makepass;
	}
}
