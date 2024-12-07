<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Util;

defined('AKEEBAENGINE') || die();

/**
 * Replacement for the utf8_encode and utf8_decode functions on PHP 8.2 and later.
 *
 * @see https://wiki.php.net/rfc/remove_utf8_decode_and_utf8_encode
 */
class Utf8
{
	public static function utf8_encode($s)
	{
		if (version_compare(PHP_VERSION, '8.1.999', 'le'))
		{
			return utf8_encode($s);
		}

		if (function_exists('mb_convert_encoding'))
		{
			return mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
		}

		if (class_exists('UConverter'))
		{
			return UConverter::transcode($s, 'UTF8', 'ISO-8859-1');
		}

		if (function_exists('iconv'))
		{
			return iconv('ISO-8859-1', 'UTF-8', $s);
		}

		/**
		 * Fallback to the pure PHP implementation from Symfony Polyfill for PHP 7.2
		 *
		 * @see https://github.com/symfony/polyfill-php72/blob/v1.26.0/Php72.php
		 */
		$s .= $s;
		$len = \strlen($s);

		for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
			switch (true) {
				case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
				case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
				default: $s[$j] = "\xC3"; $s[++$j] = \chr(\ord($s[$i]) - 64); break;
			}
		}

		return substr($s, 0, $j);
	}

	public static function utf8_decode($s)
	{
		if (version_compare(PHP_VERSION, '8.1.999', 'le'))
		{
			return utf8_decode($s);
		}

		if (function_exists('mb_convert_encoding'))
		{
			return mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
		}

		if (class_exists('UConverter'))
		{
			return UConverter::transcode($s, 'ISO-8859-1', 'UTF8');
		}

		if (function_exists('iconv'))
		{
			return iconv('UTF-8', 'ISO-8859-1', $s);
		}

		/**
		 * Fallback to the pure PHP implementation from Symfony Polyfill for PHP 7.2
		 *
		 * @see https://github.com/symfony/polyfill-php72/blob/v1.26.0/Php72.php
		 */
		$s = (string) $s;
		$len = \strlen($s);

		for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
			switch ($s[$i] & "\xF0") {
				case "\xC0":
				case "\xD0":
					$c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
					$s[$j] = $c < 256 ? \chr($c) : '?';
					break;

				case "\xF0":
					++$i;
				// no break

				case "\xE0":
					$s[$j] = '?';
					$i += 2;
					break;

				default:
					$s[$j] = $s[$i];
			}
		}

		return substr($s, 0, $j);
	}
}