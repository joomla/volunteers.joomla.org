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

namespace Akeeba\WebPush\ECC;

use Akeeba\WebPush\ECC\BigInteger as CoreBigInteger;
use Brick\Math\BigInteger;

/**
 * This class is copied verbatim from the JWT Framework by Spomky Labs.
 *
 * You can find the original code at https://github.com/web-token/jwt-framework
 *
 * The original file has the following copyright notice:
 *
 * =====================================================================================================================
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE-SPOMKY.txt file for details.
 *
 * =====================================================================================================================
 *
 * @internal
 */
class Math
{
    public static function equals(BigInteger $first, BigInteger $other): bool
    {
        return $first->isEqualTo($other);
    }

    public static function add(BigInteger $augend, BigInteger $addend): BigInteger
    {
        return $augend->plus($addend);
    }

    public static function toString(BigInteger $value): string
    {
        return $value->toBase(10);
    }

    public static function inverseMod(BigInteger $a, BigInteger $m): BigInteger
    {
        return CoreBigInteger::createFromBigInteger($a)->modInverse(CoreBigInteger::createFromBigInteger($m))->get();
    }

    public static function baseConvert(string $number, int $from, int $to): string
    {
        return BigInteger::fromBase($number, $from)->toBase($to);
    }
}
