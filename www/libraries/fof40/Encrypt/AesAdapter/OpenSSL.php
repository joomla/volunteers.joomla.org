<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt\AesAdapter;

defined('_JEXEC') || die;

use FOF40\Encrypt\Randval;

class OpenSSL extends AbstractAdapter implements AdapterInterface
{
	/**
	 * The OpenSSL options for encryption / decryption
	 *
	 * PHP 5.3 does not have the constants OPENSSL_RAW_DATA and OPENSSL_ZERO_PADDING. In fact, the parameter
	 * is called $raw_data and is a boolean. Since integer 1 is equivalent to boolean TRUE in PHP we can get
	 * away with initializing this parameter with the integer 1.
	 *
	 * @var  int
	 */
	protected $openSSLOptions = 1;

	/**
	 * The encryption method to use
	 *
	 * @var  string
	 */
	protected $method = 'aes-128-cbc';

	public function __construct()
	{
		/**
		 * PHP 5.4 and later replaced the $raw_data parameter with the $options parameter. Instead of a boolean we need
		 * to pass some flags.
		 *
		 * See http://stackoverflow.com/questions/24707007/using-openssl-raw-data-param-in-openssl-decrypt-with-php-5-3#24707117
		 */
		$this->openSSLOptions = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
	}

	public function setEncryptionMode(string $mode = 'cbc'): void
	{
		static $availableAlgorithms = null;
		static $defaultAlgo = 'aes-128-cbc';

		if (!is_array($availableAlgorithms))
		{
			$availableAlgorithms = openssl_get_cipher_methods();

			foreach ([
				         'aes-256-cbc', 'aes-256-ecb', 'aes-192-cbc',
				         'aes-192-ecb', 'aes-128-cbc', 'aes-128-ecb',
			         ] as $algo)
			{
				if (in_array($algo, $availableAlgorithms))
				{
					$defaultAlgo = $algo;
					break;
				}
			}
		}

		$mode = strtolower($mode);

		if (!in_array($mode, ['cbc', 'ebc']))
		{
			$mode = 'cbc';
		}

		$algo = 'aes-128-' . $mode;

		if (!in_array($algo, $availableAlgorithms))
		{
			$algo = $defaultAlgo;
		}

		$this->method = $algo;
	}

	public function encrypt(string $plainText, string $key, ?string $iv = null): string
	{
		$iv_size = $this->getBlockSize();
		$key     = $this->resizeKey($key, $iv_size);
		$iv      = $this->resizeKey($iv, $iv_size);

		if (empty($iv))
		{
			$randVal = new Randval();
			$iv      = $randVal->generate($iv_size);
		}

		$plainText  .= $this->getZeroPadding($plainText, $iv_size);
		$cipherText = openssl_encrypt($plainText, $this->method, $key, $this->openSSLOptions, $iv);

		return $iv . $cipherText;
	}

	public function decrypt(string $cipherText, string $key): string
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);

		return openssl_decrypt($cipherText, $this->method, $key, $this->openSSLOptions, $iv);
	}

	public function isSupported(): bool
	{
		if (!\function_exists('openssl_get_cipher_methods'))
		{
			return false;
		}

		if (!\function_exists('openssl_random_pseudo_bytes'))
		{
			return false;
		}

		if (!\function_exists('openssl_cipher_iv_length'))
		{
			return false;
		}

		if (!\function_exists('openssl_encrypt'))
		{
			return false;
		}

		if (!\function_exists('openssl_decrypt'))
		{
			return false;
		}

		if (!\function_exists('hash'))
		{
			return false;
		}

		if (!\function_exists('hash_algos'))
		{
			return false;
		}

		$algorithms = \openssl_get_cipher_methods();

		if (!in_array('aes-128-cbc', $algorithms))
		{
			return false;
		}

		$algorithms = \hash_algos();

		return in_array('sha256', $algorithms);
	}

	/**
	 * @return int
	 */
	public function getBlockSize(): int
	{
		return openssl_cipher_iv_length($this->method);
	}
}
