<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Interface for Encapsulation data handlers
 */
interface EncapsulationInterface
{
	/**
	 * Is the provided encapsulation type supported by this class?
	 *
	 * @param   int  $encapsulation  Encapsulation type
	 *
	 * @return  bool  True if supported
	 */
	public function isSupported($encapsulation);

	/**
	 * Returns information about the encapsulation supported by this class. The return array has the following keys:
	 * id: The numeric ID of the encapsulation, e.g. 3
	 * code: The short code of the encapsulation, e.g. ENCAPSULATION_AESCTR256
	 * description: A human readable descriptions, e.g. "Data in AES-256 stream (CTR) mode encrypted JSON"
	 *
	 * @return  array  See above
	 */
	public function getInformation();

	/**
	 * Decodes the data. For encrypted encapsulations this means base64-decoding the data, decrypting it but *NOT* JSON-
	 * decoding the result. If any error occurs along the way the appropriate exception is thrown.
	 *
	 * The data being decoded corresponds to the Request Body described in the API documentation
	 *
	 * @param   string  $serverKey  The server key we need to decode data
	 * @param   string  $data       Encoded data
	 *
	 * @return  string  The decoded data.
	 *
	 * @throws  \RuntimeException  When the server capabilities don't match the requested encapsulation
	 * @throws  \InvalidArgumentException  When $data cannot be decoded successfully
	 *
	 * @see     https://www.akeebabackup.com/documentation/json-api/ar01s02.html
	 */
	public function decode($serverKey, $data);

	/**
	 * Encodes the data. The data is JSON encoded by this method before encapsulation takes place. Encrypted
	 * encapsulations will then encrypt the data and base64-encode it before returning it.
	 *
	 * The data being encoded correspond to the body > data structure described in the API documentation
	 *
	 * @param   string  $serverKey  The server key we need to encode data
	 * @param   mixed   $data       The data to encode, typically a string, array or object
	 *
	 * @return  string  The encapsulated data
	 *
	 * @see     https://www.akeebabackup.com/documentation/json-api/ar01s02s02.html
	 *
	 * @throws  \RuntimeException  When the server capabilities don't match the requested encapsulation
	 * @throws  \InvalidArgumentException  When $data cannot be converted to JSON
	 */
	public function encode($serverKey, $data);

	/**
	 * Checks if the request body authorises the user to use the API. Each encapsulation can implement its own
	 * authorisation method. This method is only called after the request body has been successfully decoded, therefore
	 * encrypted encapsulations can simply return true.
	 *
	 * @param   string  $serverKey  The server key we need to check the authorisation
	 * @param   array   $body       The decoded body (as returned by the decode() method)
	 *
	 * @return  bool  True if authorised
	 */
	public function isAuthorised($serverKey, $body);
}
