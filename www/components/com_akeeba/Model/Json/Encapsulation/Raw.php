<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Encapsulation;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Raw (plain text) encapsulation
 */
class Raw extends Base
{
	/**
	 * Constructs the encapsulation handler object
	 */
	function __construct()
	{
		parent::__construct(1, 'ENCAPSULATION_RAW', 'Data in plain-text JSON');
	}

	/**
	 * Decodes the data. For encrypted encapsulations this means base64-decoding the data, decrypting it and then JSON-
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
	public function decode($serverKey, $data)
	{
		return $data;
	}

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
	public function encode($serverKey, $data)
	{
		return $data;
	}

	/**
	 * Checks if the request body authorises the user to use the API. Each encapsulation can implement its own
	 * authorisation method. This method is only called after the request body has been successfully decoded, therefore
	 * encrypted encapsulations can simply return true.
	 *
	 * @param   string $serverKey The server key we need to check the authorisation
	 * @param   array  $body      The decoded body (as returned by the decode() method)
	 *
	 * @return  bool  True if authorised
	 */
	public function isAuthorised($serverKey, $body)
	{
		$authenticated = false;

		if (isset($body['challenge']) && (strpos($body['challenge'], ':') >= 2) && (strlen($body['challenge']) >= 3))
		{
			list ($challengeData, $providedHash) = explode(':', $body['challenge']);
			$computedHash = strtolower(md5($challengeData . $serverKey));
			$authenticated = ($computedHash == $providedHash);
		}

		return $authenticated;
	}

}
