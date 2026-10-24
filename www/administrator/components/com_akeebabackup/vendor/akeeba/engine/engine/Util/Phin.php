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

defined('AKEEBAENGINE') || die();

class Phin
{
	private static array $map = [
		'a' => 'k', 'b' => 'm', 'c' => 'z', 'd' => 'p', 'e' => 'h',
		'f' => 'q', 'g' => 'w', 'h' => 'y', 'i' => 'x', 'j' => 'n',
		'k' => 'v', 'l' => 'u', 'm' => 't', 'n' => 's', 'o' => 'r',
		'p' => 'j', 'q' => 'o', 'r' => 'i', 's' => 'g', 't' => 'f',
		'u' => 'e', 'v' => 'd', 'w' => 'c', 'x' => 'b', 'y' => 'a',
		'z' => 'l',
		'A' => 'N', 'B' => 'Q', 'C' => 'K', 'D' => 'Z', 'E' => 'V',
		'F' => 'J', 'G' => 'X', 'H' => 'B', 'I' => 'M', 'J' => 'F',
		'K' => 'W', 'L' => 'P', 'M' => 'S', 'N' => 'H', 'O' => 'C',
		'P' => 'E', 'Q' => 'Y', 'R' => 'G', 'S' => 'T', 'T' => 'A',
		'U' => 'L', 'V' => 'O', 'W' => 'R', 'X' => 'D', 'Y' => 'U',
		'Z' => 'I',
	];

	public static function cee(string $string): string
	{
		return strtr($string, self::$map);
	}

	public static function artoo(string $string): string
	{
		return strtr($string, array_flip(self::$map));
	}
}
