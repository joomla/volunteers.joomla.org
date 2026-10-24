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

namespace Akeeba\WebPush\WebPush;

use function mb_strlen;
use function mb_substr;

/**
 * This class is a derivative work based on the WebPush library by Louis Lagrange. It has been modified to only use
 * dependencies shipped with Joomla itself and must not be confused with the original work.
 *
 * You can find the original code at https://github.com/web-push-libs
 *
 * The original code came with the following copyright notice:
 *
 * =====================================================================================================================
 *
 * This file is part of the WebPush library.
 *
 * (c) Louis Lagrange <lagrange.louis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE-LAGRANGE.txt
 * file that was distributed with this source code.
 *
 * =====================================================================================================================
 */
class Utils
{
	public static function safeStrlen(string $value): int
	{
		return mb_strlen($value, '8bit');
	}

	public static function serializePublicKeyFromData(array $data): string
	{
		$hexString = '04';
		$hexString .= str_pad(bin2hex($data['x']), 64, '0', STR_PAD_LEFT);
		$hexString .= str_pad(bin2hex($data['y']), 64, '0', STR_PAD_LEFT);

		return $hexString;
	}

	public static function unserializePublicKey(string $data): array
	{
		$data = bin2hex($data);

		if (mb_substr($data, 0, 2, '8bit') !== '04')
		{
			throw new \InvalidArgumentException('Invalid data: only uncompressed keys are supported.');
		}

		$data       = mb_substr($data, 2, null, '8bit');
		$dataLength = self::safeStrlen($data);

		return [
			hex2bin(mb_substr($data, 0, $dataLength / 2, '8bit')),
			hex2bin(mb_substr($data, $dataLength / 2, null, '8bit')),
		];
	}
}
