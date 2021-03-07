<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt;

defined('_JEXEC') || die;

use FOF40\Encrypt\AesAdapter\AdapterInterface;
use FOF40\Encrypt\AesAdapter\OpenSSL;

/**
 * A simple abstraction to AES encryption
 *
 * Usage:
 *
 * // Create a new instance.
 * $aes = new Aes();
 * // Set the encryption password. It's expanded to a key automatically.
 * $aes->setPassword('yourPassword');
 * // Encrypt something.
 * $cipherText = $aes->encryptString($sourcePlainText);
 * // Decrypt something
 * $plainText = $aes->decryptString($sourceCipherText);
 */
class Aes
{
	/**
	 * The cipher key.
	 *
	 * @var   string
	 */
	private $key = '';

	/**
	 * The AES encryption adapter in use.
	 *
	 * @var  AdapterInterface
	 */
	private $adapter;

	/**
	 * Initialise the AES encryption object.
	 *
	 * @param   string   $mode     Encryption mode. Can be ebc or cbc. We recommend using cbc.
	 */
	public function __construct(string $mode = 'cbc')
	{
		$this->adapter = new OpenSSL();

		$this->adapter->setEncryptionMode($mode);
	}

	/**
	 * Is AES encryption supported by this PHP installation?
	 *
	 * @return boolean
	 */
	public static function isSupported(): bool
	{
		$adapter = new OpenSSL();

		if (!$adapter->isSupported())
		{
			return false;
		}

		if (!\function_exists('base64_encode'))
		{
			return false;
		}

		if (!\function_exists('base64_decode'))
		{
			return false;
		}

		if (!\function_exists('hash_algos'))
		{
			return false;
		}

		$algorithms = \hash_algos();

		return in_array('sha256', $algorithms);
	}

	/**
	 * Sets the password for this instance.
	 *
	 * @param   string  $password  The password (either user-provided password or binary encryption key) to use
	 */
	public function setPassword(string $password)
	{
		$this->key = $password;
	}

	/**
	 * Encrypts a string using AES
	 *
	 * @param   string  $stringToEncrypt  The plaintext to encrypt
	 * @param   bool    $base64encoded    Should I Base64-encode the result?
	 *
	 * @return   string  The cryptotext. Please note that the first 16 bytes of
	 *                   the raw string is the IV (initialisation vector) which
	 *                   is necessary for decoding the string.
	 */
	public function encryptString(string $stringToEncrypt, bool $base64encoded = true): string
	{
		$blockSize = $this->adapter->getBlockSize();
		$randVal   = new Randval();
		$iv        = $randVal->generate($blockSize);

		$key        = $this->getExpandedKey($blockSize, $iv);
		$cipherText = $this->adapter->encrypt($stringToEncrypt, $key, $iv);

		// Optionally pass the result through Base64 encoding
		if ($base64encoded)
		{
			$cipherText = base64_encode($cipherText);
		}

		// Return the result
		return $cipherText;
	}

	/**
	 * Decrypts a ciphertext into a plaintext string using AES
	 *
	 * @param   string  $stringToDecrypt  The ciphertext to decrypt. The first 16 bytes of the raw string must contain
	 *                                    the IV (initialisation vector).
	 * @param   bool    $base64encoded    Should I Base64-decode the data before decryption?
	 * @param   bool    $legacy           Use legacy key expansion? Use it to decrypt date encrypted with FOF 3.
	 *
	 * @return   string  The plain text string
	 */
	public function decryptString(string $stringToDecrypt, bool $base64encoded = true, bool $legacy = false): string
	{
		if ($base64encoded)
		{
			$stringToDecrypt = base64_decode($stringToDecrypt);
		}

		// Extract IV
		$iv_size = $this->adapter->getBlockSize();
		$strLen  = function_exists('mb_strlen') ? mb_strlen($stringToDecrypt, 'ASCII') : strlen($stringToDecrypt);

		// If the string is not big enough to have an Initialization Vector in front then, clearly, it is not encrypted.
		if ($strLen < $iv_size)
		{
			return '';
		}

		// Get the IV, the key and decrypt the string
		$iv  = substr($stringToDecrypt, 0, $iv_size);
		$key = $this->getExpandedKey($iv_size, $iv, $legacy);

		return $this->adapter->decrypt($stringToDecrypt, $key);
	}

	/**
	 * Performs key expansion using PBKDF2
	 *
	 * CAVEAT: If your password ($this->key) is the same size as $blockSize you don't get key expansion. Practically,
	 * it means that you should avoid using 16 byte passwords.
	 *
	 * @param   int     $blockSize  Block size in bytes. This should always be 16 since we only deal with 128-bit AES
	 *                              here.
	 * @param   string  $iv         The initial vector. Use Randval::generate($blockSize)
	 * @param   bool    $legacy     Use legacy key expansion? Only ever use to decrypt data encrypted with FOF 3.
	 *
	 * @return string
	 */
	public function getExpandedKey(int $blockSize, string $iv, bool $legacy = false): string
	{
		$key        = $legacy ? $this->legacyKey($this->key) : $this->key;
		$passLength = strlen($key);

		if (function_exists('mb_strlen'))
		{
			$passLength = mb_strlen($key, 'ASCII');
		}

		if ($passLength !== $blockSize)
		{
			$iterations = 1000;
			$salt       = $this->adapter->resizeKey($iv, 16);
			$key        = hash_pbkdf2('sha256', $this->key, $salt, $iterations, $blockSize, true);
		}

		return $key;
	}

	/**
	 * Process the password the same way FOF 3 did.
	 *
	 * This is a very bad idea. It would get a password, calculate its SHA-256 and throw half of it away. The rest was
	 * used as the encryption key. In FOF 4 we use a far more sane key expansion using PKKDF2 with SHA-256 and 1000
	 * rounds.
	 *
	 * @param   $password
	 *
	 * @return  string
	 * @since   4.0.0
	 */
	private function legacyKey($password): string
	{
		$passLength = strlen($password);

		if (function_exists('mb_strlen'))
		{
			$passLength = mb_strlen($password, 'ASCII');
		}

		if ($passLength === 32)
		{
			return $password;
		}

		// Legacy mode was doing something stupid, requiring a key of 32 bytes. DO NOT USE LEGACY MODE!
		// Legacy mode: use the sha256 of the password
		$key = hash('sha256', $password, true);
		// We have to trim or zero pad the password (we end up throwing half of it away in Rijndael-128 / AES...)
		$key = $this->adapter->resizeKey($key, $this->adapter->getBlockSize());

		return $key;
	}
}

/**
 * Compatibility mode for servers lacking the hash_pbkdf2 PHP function (typically, the hash extension is installed but
 * PBKDF2 was not compiled into it). This is really slow but since it's used sparingly you shouldn't notice a
 * substantial performance degradation under most circumstances.
 */
if (!function_exists('hash_pbkdf2'))
{
	function hash_pbkdf2($algo, $password, $salt, $count, $length = 0, $raw_output = false)
	{
		if (!in_array(strtolower($algo), hash_algos()))
		{
			trigger_error(__FUNCTION__ . '(): Unknown hashing algorithm: ' . $algo, E_USER_WARNING);
		}

		if (!is_numeric($count))
		{
			trigger_error(__FUNCTION__ . '(): expects parameter 4 to be long, ' . gettype($count) . ' given', E_USER_WARNING);
		}

		if (!is_numeric($length))
		{
			trigger_error(__FUNCTION__ . '(): expects parameter 5 to be long, ' . gettype($length) . ' given', E_USER_WARNING);
		}

		if ($count <= 0)
		{
			trigger_error(__FUNCTION__ . '(): Iterations must be a positive integer: ' . $count, E_USER_WARNING);
		}

		if ($length < 0)
		{
			trigger_error(__FUNCTION__ . '(): Length must be greater than or equal to 0: ' . $length, E_USER_WARNING);
		}

		$output      = '';
		$block_count = $length ? ceil($length / strlen(hash($algo, '', $raw_output))) : 1;

		for ($i = 1; $i <= $block_count; $i++)
		{
			$last = $xorsum = hash_hmac($algo, $salt . pack('N', $i), $password, true);

			for ($j = 1; $j < $count; $j++)
			{
				$xorsum ^= ($last = hash_hmac($algo, $last, $password, true));
			}

			$output .= $xorsum;
		}

		if (!$raw_output)
		{
			$output = bin2hex($output);
		}

		return $length ? substr($output, 0, $length) : $output;
	}
}
