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

use Akeeba\WebPush\Base64Url\Base64Url;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

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
class VAPID
{
	private const PUBLIC_KEY_LENGTH = 65;

	private const PRIVATE_KEY_LENGTH = 32;

	/**
	 * This method creates VAPID keys in case you would not be able to have a Linux bash.
	 * DO NOT create keys at each initialization! Save those keys and reuse them.
	 *
	 * @throws \ErrorException
	 */
	public static function createVapidKeys(): array
	{
		$keyData = self::createECKeyUsingOpenSSL();

		$binaryPublicKey = hex2bin(Utils::serializePublicKeyFromData($keyData));

		if (!$binaryPublicKey)
		{
			throw new \ErrorException('Failed to convert VAPID public key from hexadecimal to binary');
		}

		$binaryPrivateKey = hex2bin(str_pad(bin2hex($keyData['d']), 2 * self::PRIVATE_KEY_LENGTH, '0', STR_PAD_LEFT));

		if (!$binaryPrivateKey)
		{
			throw new \ErrorException('Failed to convert VAPID private key from hexadecimal to binary');
		}

		return [
			'publicKey'  => Base64Url::encode($binaryPublicKey),
			'privateKey' => Base64Url::encode($binaryPrivateKey),
		];
	}

	/**
	 * This method takes the required VAPID parameters and returns the required
	 * header to be added to a Web Push Protocol Request.
	 *
	 * @param   string    $audience    This must be the origin of the push service
	 * @param   string    $subject     This should be a URL or a 'mailto:' email address
	 * @param   string    $publicKey   The decoded VAPID public key
	 * @param   string    $signingKey  The decoded VAPID private key
	 * @param   null|int  $expiration  The expiration of the VAPID JWT. (UNIX timestamp)
	 *
	 * @return array Returns an array with the 'Authorization' and 'Crypto-Key' values to be used as headers
	 * @throws \ErrorException
	 */
	public static function getVapidHeaders(
		string $audience, string $subject, string $publicKey, string $signingKey, string $contentEncoding,
		?int $expiration = null
	)
	{
		// Get the full key data from the public and private key
		$keyData   = Utils::unserializePublicKey($publicKey);
		$keyData[] = $signingKey;
		$keyData   = array_combine(['x', 'y', 'd'], $keyData);
		$keyData   = array_map([Base64Url::class, 'encode'], $keyData);

		// Get an in-memory key (see https://github.com/lcobucci/jwt/blob/3.4.x/docs/configuration.md)
		$privateKeyPem   = Encryption::convertPrivateKeyToPEM($keyData);
		$publicKeyPem    = Encryption::convertPublicKeyToPEM($keyData);
		$signingKey      = InMemory::plainText($privateKeyPem);
		$verificationKey = InMemory::plainText($publicKeyPem);

		// Calculate expiration date and time
		$expirationLimit = time() + 43200; // equal margin of error between 0 and 24h
		if (null === $expiration || $expiration > $expirationLimit)
		{
			$expiration = $expirationLimit;
		}
		// Get current data and time
		// Get the JWT
		$configuration = Configuration::forAsymmetricSigner(new Sha256(), $signingKey, $verificationKey);
		$token         = $configuration->builder()
			->expiresAt(new DateTimeImmutable('@' . $expiration))
			->issuedAt(new DateTimeImmutable())
			->permittedFor($audience)
			->relatedTo($subject)
			->getToken($configuration->signer(), $configuration->signingKey());
		$jwt           = $token->toString();

		// Get the authorisation headers
		$encodedPublicKey = Base64Url::encode($publicKey);

		if ($contentEncoding === "aesgcm")
		{
			return [
				'Authorization' => 'WebPush ' . $jwt,
				'Crypto-Key'    => 'p256ecdsa=' . $encodedPublicKey,
			];
		}

		if ($contentEncoding === 'aes128gcm')
		{
			return [
				'Authorization' => 'vapid t=' . $jwt . ', k=' . $encodedPublicKey,
			];
		}

		throw new \ErrorException('This content encoding is not supported');
	}

	/**
	 * @throws \ErrorException
	 */
	public static function validate(array $vapid): array
	{
		if (!isset($vapid['subject']))
		{
			throw new \ErrorException('[VAPID] You must provide a subject that is either a mailto: or a URL.');
		}

		if (!isset($vapid['publicKey']))
		{
			throw new \ErrorException('[VAPID] You must provide a public key.');
		}

		$publicKey = Base64Url::decode($vapid['publicKey']);

		if (Utils::safeStrlen($publicKey) !== self::PUBLIC_KEY_LENGTH)
		{
			throw new \ErrorException('[VAPID] Public key should be 65 bytes long when decoded.');
		}

		if (!isset($vapid['privateKey']))
		{
			throw new \ErrorException('[VAPID] You must provide a private key.');
		}

		$privateKey = Base64Url::decode($vapid['privateKey']);

		if (Utils::safeStrlen($privateKey) !== self::PRIVATE_KEY_LENGTH)
		{
			throw new \ErrorException('[VAPID] Private key should be 32 bytes long when decoded.');
		}

		return [
			'subject'    => $vapid['subject'],
			'publicKey'  => $publicKey,
			'privateKey' => $privateKey,
		];
	}

	/**
	 * Create a new elliptic curve key using the P-256 curve and OpenSSL
	 *
	 * @throws \RuntimeException if the extension OpenSSL is not available
	 * @throws \RuntimeException if the key cannot be created
	 */
	private static function createECKeyUsingOpenSSL(): array
	{
		if (!extension_loaded('openssl'))
		{
			throw new \RuntimeException('Please install the OpenSSL extension');
		}
		$key = openssl_pkey_new(
			[
				'curve_name'       => 'prime256v1',
				'private_key_type' => OPENSSL_KEYTYPE_EC,
			]
		);

		if ($key === false)
		{
			throw new \RuntimeException('Unable to create the key');
		}

		$result = openssl_pkey_export($key, $out);

		if ($result === false)
		{
			throw new \RuntimeException('Unable to create the key');
		}

		$res = openssl_pkey_get_private($out);

		if ($res === false)
		{
			throw new \RuntimeException('Unable to create the key');
		}

		$details = openssl_pkey_get_details($res);

		if ($details === false)
		{
			throw new \InvalidArgumentException('Unable to get the key details');
		}

		return [
			'd' => $details['ec']['d'],
			'x' => $details['ec']['x'],
			'y' => $details['ec']['y'],
		];
	}

}
