<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Site\Model\Json\Encapsulation;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Site\Model\Json\EncapsulationInterface;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Encrypt;

abstract class Base implements EncapsulationInterface
{
	/**
	 * The numeric ID of this encapsulation
	 *
	 * @var  int
	 */
	protected $id = 0;

	/**
	 * The code of this encapsulation
	 *
	 * @var  string
	 */
	protected $code = 'ENCAPSULATION_VOID';

	/**
	 * The description of this encapsulation
	 *
	 * @var  string
	 */
	protected $description = 'Invalid encapsulation';

	/**
	 * The encryption object which is set up for use with the JSON API
	 *
	 * @var  Encrypt
	 */
	private $encryption;

	/**
	 * Public constructor. Called by children to customise the encapsulation handler object
	 *
	 * @param   int     $id           Numeric ID
	 * @param   string  $code         Code
	 * @param   string  $description  Human readable description
	 */
	function __construct($id, $code, $description)
	{
		$this->id          = $id;
		$this->code        = strtoupper($code);
		$this->description = $description;
	}

	/**
	 * Returns information about the encapsulation supported by this class. The return array has the following keys:
	 * id: The numeric ID of the encapsulation, e.g. 3
	 * code: The short code of the encapsulation, e.g. ENCAPSULATION_AESCTR256
	 * description: A human readable descriptions, e.g. "Data in AES-256 stream (CTR) mode encrypted JSON"
	 *
	 * @return  array  See above
	 */
	public function getInformation()
	{
		return array(
			'id'          => $this->id,
			'code'        => $this->code,
			'description' => $this->description,
		);
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
		return true;
	}

	/**
	 * Is the provided encapsulation type supported by this class?
	 *
	 * @param   int  $encapsulation  Encapsulation type
	 *
	 * @return  bool  True if supported
	 */
	public function isSupported($encapsulation)
	{
		return $encapsulation == $this->id;
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
}
