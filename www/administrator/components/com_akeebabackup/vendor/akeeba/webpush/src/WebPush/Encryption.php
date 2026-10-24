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
use Akeeba\WebPush\ECC\Curve;
use Akeeba\WebPush\ECC\Point;
use Akeeba\WebPush\ECC\PrivateKey;
use Brick\Math\BigInteger;
use function chr;
use function hex2bin;
use function is_array;
use function mb_substr;
use function openssl_encrypt;
use function pack;
use function str_pad;
use function unpack;
use const false;
use const OPENSSL_RAW_DATA;
use const STR_PAD_LEFT;

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
class Encryption
{
	public const MAX_PAYLOAD_LENGTH = 4078;

	public const MAX_COMPATIBILITY_PAYLOAD_LENGTH = 3052;

	/**
	 * @param   string  $payload        With padding
	 * @param   string  $userPublicKey  Base 64 encoded (MIME or URL-safe)
	 * @param   string  $userAuthToken  Base 64 encoded (MIME or URL-safe)
	 *
	 * @throws \ErrorException
	 */
	public static function encrypt(string $payload, string $userPublicKey, string $userAuthToken, string $contentEncoding): array
	{
		$localKeyData = self::createLocalKeyObjectUsingOpenSSL();
		$salt         = random_bytes(16);

		$userPublicKey = Base64Url::decode($userPublicKey);
		$userAuthToken = Base64Url::decode($userAuthToken);

		// get local key pair
		$localPublicKey = hex2bin(Utils::serializePublicKeyFromData($localKeyData));

		if (!$localPublicKey)
		{
			throw new \ErrorException('Failed to convert local public key from hexadecimal to binary');
		}

		// get user public key object
		[$userPublicKeyObjectX, $userPublicKeyObjectY] = Utils::unserializePublicKey($userPublicKey);

		$userKeyData = [
			'x'   => $userPublicKeyObjectX,
			'y'   => $userPublicKeyObjectY,
		];

		// get shared secret from user public key and local private key
		$sharedSecret = Encryption::calculateAgreementKey($localKeyData, $userKeyData);

		$sharedSecret = str_pad($sharedSecret, 32, chr(0), STR_PAD_LEFT);

		// section 4.3
		$ikm = Encryption::getIKM($userAuthToken, $userPublicKey, $localPublicKey, $sharedSecret, $contentEncoding);

		// section 4.2
		$context = Encryption::createContext($userPublicKey, $localPublicKey, $contentEncoding);

		// derive the Content Encryption Key
		$contentEncryptionKeyInfo = Encryption::createInfo($contentEncoding, $context, $contentEncoding);
		$contentEncryptionKey     = Encryption::hkdf($salt, $ikm, $contentEncryptionKeyInfo, 16);

		// section 3.3, derive the nonce
		$nonceInfo = Encryption::createInfo('nonce', $context, $contentEncoding);
		$nonce     = Encryption::hkdf($salt, $ikm, $nonceInfo, 12);

		// encrypt
		// "The additional data passed to each invocation of AEAD_AES_128_GCM is a zero-length octet sequence."
		$tag           = '';
		$encryptedText = openssl_encrypt($payload, 'aes-128-gcm', $contentEncryptionKey, OPENSSL_RAW_DATA, $nonce, $tag);

		// return values in url safe base64
		return [
			'localPublicKey' => $localPublicKey,
			'salt'           => $salt,
			'cipherText'     => $encryptedText . $tag,
		];
	}

	public static function getContentCodingHeader(string $salt, string $localPublicKey, string $contentEncoding): string
	{
		if ($contentEncoding === "aes128gcm")
		{
			return $salt
				. pack('N*', 4096)
				. pack('C*', Utils::safeStrlen($localPublicKey))
				. $localPublicKey;
		}

		return "";
	}

	/**
	 * @return string padded payload (plaintext)
	 * @throws \ErrorException
	 */
	public static function padPayload(string $payload, int $maxLengthToPad, string $contentEncoding): string
	{
		$payloadLen = Utils::safeStrlen($payload);
		$padLen     = $maxLengthToPad ? $maxLengthToPad - $payloadLen : 0;

		if ($contentEncoding === "aesgcm")
		{
			return pack('n*', $padLen) . str_pad($payload, $padLen + $payloadLen, chr(0), STR_PAD_LEFT);
		}
		elseif ($contentEncoding === "aes128gcm")
		{
			return str_pad($payload . chr(2), $padLen + $payloadLen, chr(0), STR_PAD_RIGHT);
		}
		else
		{
			throw new \ErrorException("This content encoding is not supported");
		}
	}

	private static function addNullPadding(string $data): string
	{
		return str_pad($data, 32, chr(0), STR_PAD_LEFT);
	}

	private static function calculateAgreementKey(array $private_key, array $public_key): string
	{
		if (function_exists('openssl_pkey_derive'))
		{
			try
			{
				$publicPem  = self::convertPublicKeyToPEM($public_key);
				$private_key = array_map([Base64Url::class, 'encode'], $private_key);
				$privatePem = self::convertPrivateKeyToPEM($private_key);

				$result = openssl_pkey_derive($publicPem, $privatePem, 256);
				if ($result === false)
				{
					throw new \Exception('Unable to compute the agreement key');
				}

				return $result;
			}
			catch (\Throwable $throwable)
			{
				//Does nothing. Will fallback to the pure PHP function
			}
		}


		$curve = self::curve256();

		$rec_x    = self::convertBase64ToBigInteger($public_key['x']);
		$rec_y    = self::convertBase64ToBigInteger($public_key['y']);
		$sen_d    = self::convertBase64ToBigInteger($private_key['d']);
		$priv_key = PrivateKey::create($sen_d);
		$pub_key  = $curve->getPublicKeyFrom($rec_x, $rec_y);

		return hex2bin(str_pad($curve->mul($pub_key->getPoint(), $priv_key->getSecret())->getX()->toBase(16), 64, '0', STR_PAD_LEFT));
	}

	/**
	 * @throws \ErrorException
	 */
	private static function convertBase64ToBigInteger(string $value): BigInteger
	{
		try
		{
			$value = unpack('H*', Base64Url::decode($value));
		}
		catch (\Exception $e)
		{
			$value = unpack('H*', $value);
		}

		if ($value === false)
		{
			throw new \ErrorException('Unable to unpack hex value from string');
		}

		return BigInteger::fromBase($value[1], 16);
	}

	/**
	 * @throws \ErrorException
	 */
	private static function convertBase64ToGMP(string $value): \GMP
	{
		$value = unpack('H*', Base64Url::decode($value));

		if ($value === false)
		{
			throw new \ErrorException('Unable to unpack hex value from string');
		}

		return gmp_init($value[1], 16);
	}

	/**
	 * Creates a context for deriving encryption parameters.
	 * See section 4.2 of
	 * {@link https://tools.ietf.org/html/draft-ietf-httpbis-encryption-encoding-00}
	 * From {@link https://github.com/GoogleChrome/push-encryption-node/blob/master/src/encrypt.js}.
	 *
	 * @param   string  $clientPublicKey  The client's public key
	 * @param   string  $serverPublicKey  Our public key
	 *
	 * @throws \ErrorException
	 */
	private static function createContext(string $clientPublicKey, string $serverPublicKey, string $contentEncoding): ?string
	{
		if ($contentEncoding === "aes128gcm")
		{
			return null;
		}

		if (Utils::safeStrlen($clientPublicKey) !== 65)
		{
			throw new \ErrorException('Invalid client public key length');
		}

		// This one should never happen, because it's our code that generates the key
		if (Utils::safeStrlen($serverPublicKey) !== 65)
		{
			throw new \ErrorException('Invalid server public key length');
		}

		$len = chr(0) . 'A'; // 65 as Uint16BE

		return chr(0) . $len . $clientPublicKey . $len . $serverPublicKey;
	}

	/**
	 * Returns an info record. See sections 3.2 and 3.3 of
	 * {@link https://tools.ietf.org/html/draft-ietf-httpbis-encryption-encoding-00}
	 * From {@link https://github.com/GoogleChrome/push-encryption-node/blob/master/src/encrypt.js}.
	 *
	 * @param   string       $type     The type of the info record
	 * @param   string|null  $context  The context for the record
	 *
	 * @throws \ErrorException
	 */
	private static function createInfo(string $type, ?string $context, string $contentEncoding): string
	{
		if ($contentEncoding === "aesgcm")
		{
			if (!$context)
			{
				throw new \ErrorException('Context must exist');
			}

			if (Utils::safeStrlen($context) !== 135)
			{
				throw new \ErrorException('Context argument has invalid size');
			}

			return 'Content-Encoding: ' . $type . chr(0) . 'P-256' . $context;
		}
		elseif ($contentEncoding === "aes128gcm")
		{
			return 'Content-Encoding: ' . $type . chr(0);
		}

		throw new \ErrorException('This content encoding is not supported.');
	}

	private static function createLocalKeyObjectUsingOpenSSL(): array
	{
		$keyResource = openssl_pkey_new([
			'curve_name'       => 'prime256v1',
			'private_key_type' => OPENSSL_KEYTYPE_EC,
		]);

		if (!$keyResource)
		{
			throw new \RuntimeException('Unable to create the key');
		}

		$details = openssl_pkey_get_details($keyResource);
		if (PHP_MAJOR_VERSION < 8)
		{
			openssl_pkey_free($keyResource);
		}

		if (!$details)
		{
			throw new \RuntimeException('Unable to get the key details');
		}

		return [
			'x' => self::addNullPadding($details['ec']['x']),
			'y' => self::addNullPadding($details['ec']['y']),
			'd' => self::addNullPadding($details['ec']['d']),
		];
	}

	/**
	 * @throws \ErrorException
	 */
	private static function getIKM(string $userAuthToken, string $userPublicKey, string $localPublicKey, string $sharedSecret, string $contentEncoding): string
	{
		if (!empty($userAuthToken))
		{
			if ($contentEncoding === "aesgcm")
			{
				$info = 'Content-Encoding: auth' . chr(0);
			}
			elseif ($contentEncoding === "aes128gcm")
			{
				$info = "WebPush: info" . chr(0) . $userPublicKey . $localPublicKey;
			}
			else
			{
				throw new \ErrorException("This content encoding is not supported");
			}

			return self::hkdf($userAuthToken, $sharedSecret, $info, 32);
		}

		return $sharedSecret;
	}

	/**
	 * HMAC-based Extract-and-Expand Key Derivation Function (HKDF).
	 *
	 * This is used to derive a secure encryption key from a mostly-secure shared
	 * secret.
	 *
	 * This is a partial implementation of HKDF tailored to our specific purposes.
	 * In particular, for us the value of N will always be 1, and thus T always
	 * equals HMAC-Hash(PRK, info | 0x01).
	 *
	 * See {@link https://www.rfc-editor.org/rfc/rfc5869.txt}
	 * From {@link https://github.com/GoogleChrome/push-encryption-node/blob/master/src/encrypt.js}
	 *
	 * @param   string  $salt    A non-secret random value
	 * @param   string  $ikm     Input keying material
	 * @param   string  $info    Application-specific context
	 * @param   int     $length  The length (in bytes) of the required output key
	 */
	private static function hkdf(string $salt, string $ikm, string $info, int $length): string
	{
		// extract
		$prk = hash_hmac('sha256', $ikm, $salt, true);

		// expand
		return mb_substr(hash_hmac('sha256', $info . chr(1), $prk, true), 0, $length, '8bit');
	}

	/**
	 * @throws \InvalidArgumentException if the curve is not supported
	 */
	public static function convertPublicKeyToPEM(array $keyData): string
	{
		$der = pack(
			'H*',
			'3059' // SEQUENCE, length 89
			.'3013' // SEQUENCE, length 19
			.'0607' // OID, length 7
			.'2a8648ce3d0201' // 1.2.840.10045.2.1 = EC Public Key
			.'0608' // OID, length 8
			.'2a8648ce3d030107' // 1.2.840.10045.3.1.7 = P-256 Curve
			.'0342' // BIT STRING, length 66
			.'00' // prepend with NUL - pubkey will follow
		);
		$der .= "\04"
		. str_pad($keyData['x'], 32, "\0", STR_PAD_LEFT)
		. str_pad($keyData['y'], 32, "\0", STR_PAD_LEFT);
		$pem = '-----BEGIN PUBLIC KEY-----'.PHP_EOL;
		$pem .= chunk_split(base64_encode($der), 64, PHP_EOL);
		$pem .= '-----END PUBLIC KEY-----'.PHP_EOL;

		return $pem;
	}

	/**
	 * @throws \InvalidArgumentException if the curve is not supported
	 */
	public static function convertPrivateKeyToPEM(array $keyData): string
	{
		$d = unpack('H*', str_pad(Base64Url::decode($keyData['d']), 32, "\0", STR_PAD_LEFT));

		if (!is_array($d) || !isset($d[1]))
		{
			throw new \InvalidArgumentException('Unable to get the private key');
		}

		$der = pack(
			'H*',
			'3077' // SEQUENCE, length 87+length($d)=32
			. '020101' // INTEGER, 1
			. '0420'   // OCTET STRING, length($d) = 32
			. $d[1]
			. 'a00a' // TAGGED OBJECT #0, length 10
			. '0608' // OID, length 8
			. '2a8648ce3d030107' // 1.3.132.0.34 = P-256 Curve
			. 'a144' //  TAGGED OBJECT #1, length 68
			. '0342' // BIT STRING, length 66
			. '00' // prepend with NUL - pubkey will follow
		);
		$der .= "\04"
			. str_pad(Base64Url::decode($keyData['x']), 32, "\0", STR_PAD_LEFT)
			. str_pad(Base64Url::decode($keyData['y']), 32, "\0", STR_PAD_LEFT);
		$pem = '-----BEGIN EC PRIVATE KEY-----'.PHP_EOL;
		$pem .= chunk_split(base64_encode($der), 64, PHP_EOL);
		$pem .= '-----END EC PRIVATE KEY-----'.PHP_EOL;

		return $pem;
	}

	/**
	 * Returns an NIST P-256 curve.
	 */
	private static function curve256(): Curve
	{
		$p = BigInteger::fromBase('ffffffff00000001000000000000000000000000ffffffffffffffffffffffff', 16);
		$a = BigInteger::fromBase('ffffffff00000001000000000000000000000000fffffffffffffffffffffffc', 16);
		$b = BigInteger::fromBase('5ac635d8aa3a93e7b3ebbd55769886bc651d06b0cc53b0f63bce3c3e27d2604b', 16);
		$x = BigInteger::fromBase('6b17d1f2e12c4247f8bce6e563a440f277037d812deb33a0f4a13945d898c296', 16);
		$y = BigInteger::fromBase('4fe342e2fe1a7f9b8ee7eb4a7c0f9e162bce33576b315ececbb6406837bf51f5', 16);
		$n = BigInteger::fromBase('ffffffff00000000ffffffffffffffffbce6faada7179e84f3b9cac2fc632551', 16);
		$generator = Point::create($x, $y, $n);

		return new Curve(256, $p, $a, $b, $generator);
	}

}
