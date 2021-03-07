<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Encrypt;

defined('_JEXEC') || die;

use FOF40\Container\Container;

/**
 * Data encryption service for FOF-based components.
 *
 * This service allows you to transparently encrypt and decrypt *text* plaintext data. Use it to provide encryption for
 * sensitive or personal data stored in your database. Please remember:
 *
 * - The default behavior is to create a file with a random key on your component's root. If the file cannot be created
 *   the encryption is turned off.
 * - The key file is only created when you access the service. If you never use this service nothing happens (for
 *   backwards compatibility).
 * - You have to manually encrypt and decrypt data. It won't happen magically.
 * - Encrypted data cannot be searched unless you implement your own, slow, search algorithm.
 * - Data encryption is meant to be used on top of, not instead of, any other security measures for your site.
 * - Data encryption only protects against exploits targeting the database. If the attacker *also* gains read access to
 *   your filesystem OR if the attacker gains read / write access to the filesystem the encryption won't protect you.
 *   This is a full compromise of your site. At this point you're pwned and nothing can protect you. If you don't
 *   understand this simple truth do NOT use encryption.
 * - This is meant as a simple and basic encryption layer. It has not been independently verified. Use at your own risk.
 *
 * This service has the following FOF application configuration parameters which can be declared under the "container"
 * key (e.g. the "name" attribute of the fof.xml elements under fof > common > container > option):
 *
 * - encrypt_key_file  The path to the key file, relative to the component's backend root and WITHOUT the .php extension
 * - encrypt_key_const The constant for the key. By default it is COMPONENTNAME_FOF_ENCRYPT_SERVICE_SECRETKEY where
 *                     COMPONENTNAME corresponds to the uppercase com_componentname without the com_ prefix.
 *
 * @package     FOF40\Encrypt
 *
 * @since       3.3.2
 */
class EncryptService
{
	/**
	 * The component's container
	 *
	 * @var    Container
	 * @since  3.3.2
	 */
	private $container;

	/**
	 * The encryption engine used by this service
	 *
	 * @var    Aes
	 * @since  3.3.2
	 */
	private $aes;

	/**
	 * EncryptService constructor.
	 *
	 * @param Container $c       The FOF component container
	 *
	 * @since   3.3.2
	 */
	public function __construct(Container $c)
	{
		$this->container = $c;
		$this->initialize();
	}

	/**
	 * Encrypt the plaintext $data and return the ciphertext prefixed by ###AES128###
	 *
	 * @param string $data The plaintext data
	 *
	 * @return  string  The ciphertext, prefixed by ###AES128###
	 *
	 * @since   3.3.2
	 */
	public function encrypt(string $data): string
	{
		if (!is_object($this->aes))
		{
			return $data;
		}

		$encrypted = $this->aes->encryptString($data, true);

		return '###AES128###' . $encrypted;
	}

	/**
	 * Decrypt the ciphertext, prefixed by ###AES128###, and return the plaintext.
	 *
	 * @param   string  $data    The ciphertext, prefixed by ###AES128###
	 * @param   bool    $legacy  Use legacy key expansion? Use it to decrypt data encrypted with FOF 3.
	 *
	 * @return  string  The plaintext data
	 *
	 * @since   3.3.2
	 */
	public function decrypt(string $data, bool $legacy = false): string
	{
		if (substr($data, 0, 12) != '###AES128###')
		{
			return $data;
		}

		$data = substr($data, 12);

		if (!is_object($this->aes))
		{
			return $data;
		}

		$decrypted = $this->aes->decryptString($data, true, $legacy);

		// Decrypted data is null byte padded. We have to remove the padding before proceeding.
		return rtrim($decrypted, "\0");
	}

	/**
	 * Initialize the AES cryptography object
	 *
	 * @return  void
	 * @since   3.3.2
	 *
	 */
	private function initialize(): void
	{
		if (is_object($this->aes))
		{
			return;
		}

		$password = $this->getPassword();

		if (empty($password))
		{
			return;
		}

		$this->aes = new Aes('cbc');
		$this->aes->setPassword($password);
	}

	/**
	 * Returns the path to the secret key file
	 *
	 * @return  string
	 *
	 * @since   3.3.2
	 */
	private function getPasswordFilePath(): string
	{
		$default  = 'encrypt_service_key';
		$baseName = $this->container->appConfig->get('container.encrypt_key_file', $default);
		$baseName = trim($baseName, '/\\');

		return $this->container->backEndPath . '/' . $baseName . '.php';
	}

	/**
	 * Get the name of the constant where the secret key is stored. Remember that this is searched first, before a new
	 * key file is created. You can define this constant anywhere in your code loaded before the encryption service is
	 * first used to prevent a key file being created.
	 *
	 * @return  string
	 *
	 * @since   3.3.2
	 */
	private function getConstantName(): string
	{
		$default = strtoupper($this->container->bareComponentName) . '_FOF_ENCRYPT_SERVICE_SECRETKEY';

		return $this->container->appConfig->get('container.encrypt_key_const', $default);
	}

	/**
	 * Returns the password used to encrypt information in the component
	 *
	 * @return  string
	 *
	 * @since   3.3.2
	 */
	private function getPassword(): string
	{
		$constantName = $this->getConstantName();

		// If we have already read the file just return the key
		if (defined($constantName))
		{
			return constant($constantName);
		}

		// Do I have a secret key file?
		$filePath = $this->getPasswordFilePath();

		// I can't get the path to the file. Cut our losses and assume we can get no key.
		if (empty($filePath))
		{
			define($constantName, '');

			return '';
		}

		// If not, try to create one.
		if (!file_exists($filePath))
		{
			$this->makePasswordFile();
		}

		// We failed to create a new file? Cut our losses and assume we can get no key.
		if (!file_exists($filePath) || !is_readable($filePath))
		{
			define($constantName, '');

			return '';
		}

		// Try to include the key file
		include_once $filePath;

		// The key file contains garbage. Treason! Cut our losses and assume we can get no key.
		if (!defined($constantName))
		{
			define($constantName, '');

			return '';
		}

		// Finally, return the key which was defined in the file (happy path).
		return constant($constantName);
	}

	/**
	 * Create a new secret key file using a long, randomly generated password. The password generator uses a crypto-safe
	 * pseudorandom number generator (PRNG) to ensure suitability of the password for encrypting data at rest.
	 *
	 * @return  void
	 *
	 * @since   3.3.2
	 */
	private function makePasswordFile(): void
	{
		// Get the path to the new secret key file.
		$filePath = $this->getPasswordFilePath();

		// I can't get the path to the file. Sorry.
		if (empty($filePath))
		{
			return;
		}

		$randval      = new Randval();
		$secretKey    = $randval->getRandomPassword(64);
		$constantName = $this->getConstantName();

		$fileContent = "<?" . 'ph' . "p\n\n";
		$fileContent .= <<< END
defined('_JEXEC') or die;

/**
 * This file is automatically generated. It contains a secret key used for encrypting data by the component. Please do
 * not remove, edit or manually replace this file. It will render your existing encrypted data unreadable forever.
 */
 
define('$constantName', '$secretKey');

END;

		$this->container->filesystem->fileWrite($filePath, $fileContent);
	}
}