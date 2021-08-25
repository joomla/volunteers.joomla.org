<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Crypt;

defined('JPATH_PLATFORM') or die;

/**
 * Crypt cipher interface.
 *
 * @since  3.0.0
 */
interface CipherInterface
{
	/**
	 * Method to decrypt a data string.
	 *
	 * @param   string  $data  The encrypted string to decrypt.
	 * @param   Key     $key   The key[/pair] object to use for decryption.
	 *
	 * @return  string  The decrypted data string.
	 *
	 * @since   3.0.0
	 */
	public function decrypt($data, Key $key);

	/**
	 * Method to encrypt a data string.
	 *
	 * @param   string  $data  The data string to encrypt.
	 * @param   Key     $key   The key[/pair] object to use for encryption.
	 *
	 * @return  string  The encrypted data string.
	 *
	 * @since   3.0.0
	 */
	public function encrypt($data, Key $key);

	/**
	 * Method to generate a new encryption key[/pair] object.
	 *
	 * @param   array  $options  Key generation options.
	 *
	 * @return  Key
	 *
	 * @since   3.0.0
	 */
	public function generateKey(array $options = array());
}
