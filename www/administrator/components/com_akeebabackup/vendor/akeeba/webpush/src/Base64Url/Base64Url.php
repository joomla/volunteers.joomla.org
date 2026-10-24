<?php
/**
 * Akeeba WebPush
 *
 * An abstraction layer for easier implementation of WebPush in Joomla components.
 *
 * @copyright Copyright (c) 2022-2026 Akeeba Ltd
 * @license   GNU GPL v3 or later; see LICENSE.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Akeeba\WebPush\Base64Url;

/*
 * This class is copied verbatim from the Base64 Url Safe library by Spomky Labs.
 *
 * You can find the original code at https://github.com/Spomky-Labs/base64url
 *
 * The original file has the following copyright notice:
 *
 * =====================================================================================================================
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE-SPOMKY.txt file for details.
 * =====================================================================================================================
 */

use InvalidArgumentException;
use function base64_decode;
use function base64_encode;
use function rtrim;
use function strtr;

/**
 * Encode and decode data into Base64 Url Safe.
 */
final class Base64Url
{
	/**
	 * @param   string  $data        The data to encode
	 * @param   bool    $usePadding  If true, the "=" padding at end of the encoded value are kept, else it is removed
	 *
	 * @return string The data encoded
	 */
	public static function encode(string $data, bool $usePadding = false): string
	{
		$encoded = strtr(base64_encode($data), '+/', '-_');

		return true === $usePadding ? $encoded : rtrim($encoded, '=');
	}

	/**
	 * @param   string  $data  The data to decode
	 *
	 * @return string The data decoded
	 * @throws InvalidArgumentException
	 *
	 */
	public static function decode(string $data): string
	{
		$decoded = base64_decode(strtr($data, '-_', '+/'), true);
		if (false === $decoded)
		{
			throw new InvalidArgumentException('Invalid data provided');
		}

		return $decoded;
	}
}