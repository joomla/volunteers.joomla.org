<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt\AesAdapter;

defined('_JEXEC') || die;

/**
 * Interface for AES encryption adapters
 */
interface AdapterInterface
{
	/**
	 * Sets the AES encryption mode.
	 *
	 * @param string $mode Choose between CBC (recommended) or ECB
	 *
	 * @return  void
	 */
	public function setEncryptionMode(string $mode = 'cbc'): void;

	/**
	 * Encrypts a string. Returns the raw binary ciphertext.
	 *
	 * WARNING: The plaintext is zero-padded to the algorithm's block size. You are advised to store the size of the
	 * plaintext and trim the string to that length upon decryption.
	 *
	 * @param string      $plainText The plaintext to encrypt
	 * @param string      $key       The raw binary key (will be zero-padded or chopped if its size is different than the block size)
	 * @param null|string $iv        The initialization vector (for CBC mode algorithms)
	 *
	 * @return  string  The raw encrypted binary string.
	 */
	public function encrypt(string $plainText, string $key, ?string $iv = null): string;

	/**
	 * Decrypts a string. Returns the raw binary plaintext.
	 *
	 * $ciphertext MUST start with the IV followed by the ciphertext, even for EBC data (the first block of data is
	 * dropped in EBC mode since there is no concept of IV in EBC).
	 *
	 * WARNING: The returned plaintext is zero-padded to the algorithm's block size during encryption. You are advised
	 * to trim the string to the original plaintext's length upon decryption. While rtrim($decrypted, "\0") sounds
	 * appealing it's NOT the correct approach for binary data (zero bytes may actually be part of your plaintext, not
	 * just padding!).
	 *
	 * @param string $cipherText The ciphertext to encrypt
	 * @param string $key        The raw binary key (will be zero-padded or chopped if its size is different than the block size)
	 *
	 * @return  string  The raw unencrypted binary string.
	 */
	public function decrypt(string $cipherText, string $key): string;

	/**
	 * Returns the encryption block size in bytes
	 *
	 * @return  int
	 */
	public function getBlockSize(): int;

	/**
	 * Is this adapter supported?
	 *
	 * @return  bool
	 */
	public function isSupported(): bool;
}
