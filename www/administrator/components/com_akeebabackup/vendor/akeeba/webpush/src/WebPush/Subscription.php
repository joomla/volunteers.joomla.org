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
class Subscription implements SubscriptionInterface
{
	/** @var null|string */
	private $authToken;

	/** @var null|string */
	private $contentEncoding;

	/** @var string */
	private $endpoint;

	/** @var null|string */
	private $publicKey;

	/**
	 * @param   string|null  $contentEncoding  (Optional) Must be "aesgcm"
	 *
	 * @throws \ErrorException
	 */
	public function __construct(
		string  $endpoint,
		?string $publicKey = null,
		?string $authToken = null,
		?string $contentEncoding = null
	)
	{
		$this->endpoint = $endpoint;

		if ($publicKey || $authToken || $contentEncoding)
		{
			$supportedContentEncodings = ['aesgcm', 'aes128gcm'];
			if ($contentEncoding && !in_array($contentEncoding, $supportedContentEncodings))
			{
				throw new \ErrorException('This content encoding (' . $contentEncoding . ') is not supported.');
			}

			$this->publicKey       = $publicKey;
			$this->authToken       = $authToken;
			$this->contentEncoding = $contentEncoding ?: "aesgcm";
		}
	}

	/**
	 * @param   array  $associativeArray  (with keys endpoint, publicKey, authToken, contentEncoding)
	 *
	 * @throws \ErrorException
	 */
	public static function create(array $associativeArray): self
	{
		if (array_key_exists('keys', $associativeArray) && is_array($associativeArray['keys']))
		{
			return new self(
				$associativeArray['endpoint'],
				$associativeArray['keys']['p256dh'] ?? null,
				$associativeArray['keys']['auth'] ?? null,
				$associativeArray['contentEncoding'] ?? "aesgcm"
			);
		}

		if (array_key_exists('publicKey', $associativeArray) || array_key_exists('authToken', $associativeArray) || array_key_exists('contentEncoding', $associativeArray))
		{
			return new self(
				$associativeArray['endpoint'],
				$associativeArray['publicKey'] ?? null,
				$associativeArray['authToken'] ?? null,
				$associativeArray['contentEncoding'] ?? "aesgcm"
			);
		}

		return new self(
			$associativeArray['endpoint']
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAuthToken(): ?string
	{
		return $this->authToken;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getContentEncoding(): ?string
	{
		return $this->contentEncoding;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEndpoint(): string
	{
		return $this->endpoint;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPublicKey(): ?string
	{
		return $this->publicKey;
	}
}
