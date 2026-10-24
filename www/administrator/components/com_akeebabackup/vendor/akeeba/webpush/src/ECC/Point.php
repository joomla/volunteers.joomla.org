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
 * *********************************************************************
 * Copyright (C) 2012 Matyas Danter.
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
 * OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 * ***********************************************************************
 *
 * @internal
 */
class Point
{
    /**
     * @var BigInteger
     */
    private $x;

    /**
     * @var BigInteger
     */
    private $y;

    /**
     * @var BigInteger
     */
    private $order;

    /**
     * @var bool
     */
    private $infinity = false;

    private function __construct(BigInteger $x, BigInteger $y, BigInteger $order, bool $infinity = false)
    {
        $this->x = $x;
        $this->y = $y;
        $this->order = $order;
        $this->infinity = $infinity;
    }

    public static function create(BigInteger $x, BigInteger $y, ?BigInteger $order = null): self
    {
        return new self($x, $y, $order ?? BigInteger::zero());
    }

    public static function infinity(): self
    {
        $zero = BigInteger::zero();

        return new self($zero, $zero, $zero, true);
    }

    public function isInfinity(): bool
    {
        return $this->infinity;
    }

    public function getOrder(): BigInteger
    {
        return $this->order;
    }

    public function getX(): BigInteger
    {
        return $this->x;
    }

    public function getY(): BigInteger
    {
        return $this->y;
    }

    public static function cswap(self $a, self $b, int $cond): void
    {
        self::cswapBigInteger($a->x, $b->x, $cond);
        self::cswapBigInteger($a->y, $b->y, $cond);
        self::cswapBigInteger($a->order, $b->order, $cond);
        self::cswapBoolean($a->infinity, $b->infinity, $cond);
    }

    private static function cswapBoolean(bool &$a, bool &$b, int $cond): void
    {
        $sa = BigInteger::of((int) $a);
        $sb = BigInteger::of((int) $b);

        self::cswapBigInteger($sa, $sb, $cond);

        $a = (bool) $sa->toBase(10);
        $b = (bool) $sb->toBase(10);
    }

    private static function cswapBigInteger(BigInteger &$sa, BigInteger &$sb, int $cond): void
    {
        $size = max(mb_strlen($sa->toBase(2), '8bit'), mb_strlen($sb->toBase(2), '8bit'));
        $mask = (string) (1 - $cond);
        $mask = str_pad('', $size, $mask, STR_PAD_LEFT);
        $mask = BigInteger::fromBase($mask, 2);
        $taA = $sa->and($mask);
        $taB = $sb->and($mask);
        $sa = $sa->xor($sb)->xor($taB);
        $sb = $sa->xor($sb)->xor($taA);
        $sa = $sa->xor($sb)->xor($taB);
    }
}
