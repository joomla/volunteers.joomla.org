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
 * Handles data encapsulation
 */
class Encapsulation
{
	/**
	 * Known encapsulation handlers
	 *
	 * @var  EncapsulationInterface[]
	 */
	protected $handlers = array();

	/**
	 * List of encapsulation types
	 *
	 * @var  array
	 */
	protected $encapsulations = array();

	/**
	 * The server key used to decrypt / encrypt data and check the authorisation
	 *
	 * @var  string
	 */
	protected $serverKey;

	/**
	 * Public constructor
	 *
	 * @param   string  $serverKey  The server key used for data encyrption/decryption and authorisation checks
	 */
	public function __construct($serverKey)
	{
		$this->serverKey = $serverKey;

		// Populate the list of encapsulation handlers
		$this->initialiseHandlers();
	}

	/**
	 * Returns the encapsulation ID given its code. For example given $code == 'ENCAPSULATION_AESCTR256' it will return
	 * the ID integer 3.
	 *
	 * @param   string  $code  The encapsulation code, e.g. ENCAPSULATION_AESCTR256
	 *
	 * @return  int  The numeric ID, e.g. 3
	 */
	public function getEncapsulationByCode($code)
	{
		$info = $this->getEncapsulationInfoByCode($code);

		return $info['id'];
	}

	/**
	 * Returns the encapsulation information array given its code. For example given $code == 'ENCAPSULATION_AESCTR256'
	 * it will return the information for the data in AES-256 stream (CTR) mode encrypted JSON type.
	 *
	 * @param   string  $code  The encapsulation code, e.g. ENCAPSULATION_AESCTR256
	 *
	 * @return  array  The information of the encapsulation handler
	 */
	public function getEncapsulationInfoByCode($code)
	{
		// Normalise the code
		$code = strtoupper($code);

		// If we have no idea what the encapsulation should be revert to raw (plain text)
		if (!isset($this->encapsulations[$code]))
		{
			return $this->encapsulations['ENCAPSULATION_RAW'];
		}

		return $this->encapsulations[$code];
	}

	/**
	 * Decodes the data. For encrypted encapsulations this means base64-decoding the data, decrypting it and then JSON-
	 * decoding the result. If any error occurs along the way the appropriate exception is thrown.
	 *
	 * The data being decoded corresponds to the Request Body described in the API documentation
	 *
	 * @param   int     $encapsulation  The encapsulation type
	 * @param   string  $data           Encoded data
	 *
	 * @return  array  The decoded data.
	 *
	 * @throw   \RuntimeException  When the server capabilities don't match the requested encapsulation
	 * @throw   \InvalidArgumentException  When $data cannot be decoded successfully
	 *
	 * @see     https://www.akeebabackup.com/documentation/json-api/ar01s02.html
	 */
	public function decode($encapsulation, $data)
	{
		$body = null;

		// Find the suitable handler and encode the data
		foreach ($this->handlers as $handler)
		{
			if ($handler->isSupported($encapsulation))
			{
				$body = $handler->decode($this->serverKey, $data);

				break;
			}
		}

		// If the data cannot be encoded throw an exception
		if (!isset($handler) || is_null($body))
		{
			throw new \RuntimeException('The requested encapsulation type is not supported', 503);
		}

		$authorised = true;
		$body = rtrim($body, chr(0));

		// Make sure it looks like a valid JSON string and is at least 12 characters (minimum valid message length)
		if ((strlen($body) < 12) || (substr($body, 0, 1) != '{') || (substr($body, -1) != '}'))
		{
			$authorised = false;
		}

		// Try to JSON decode the body
		if ($authorised)
		{
			$body = json_decode($body, true);

			if (is_null($body))
			{
				$authorised = false;
			}
			elseif (!is_array($body))
			{
				$authorised = false;
			}
		}

		// Make sure there is a requested method
		if ($authorised)
		{
			if (!isset($body['method']) || empty($body['method']))
			{
				$authorised = false;
			}
		}

		if ($authorised)
		{
			$authorised = $handler->isAuthorised($this->serverKey, $body);
		}

		if (!$authorised)
		{
			throw new \InvalidArgumentException('Authentication failed', 401);
		}

		return (array)$body;
	}

	/**
	 * Encodes the data. The data is JSON encoded by this method before encapsulation takes place. Encrypted
	 * encapsulations will then encrypt the data and base64-encode it before returning it.
	 *
	 * The data being encoded correspond to the body > data structure described in the API documentation
	 *
	 * @param   int     $encapsulation  The encapsulation type
	 * @param   mixed   $data           The data to encode, typically a string, array or object
	 * @param   string  $key            Key to use for encoding. If not provided we revert to $this->serverKey
	 *
	 * @return  string  The encapsulated data
	 *
	 * @see     https://www.akeebabackup.com/documentation/json-api/ar01s02s02.html
	 *
	 * @throw   \RuntimeException  When the server capabilities don't match the requested encapsulation
	 * @throw   \InvalidArgumentException  When $data cannot be converted to JSON
	 */
	public function encode($encapsulation, $data, $key = null)
	{
		// Try to JSON-encode the data
		$data = json_encode($data);

		// If the data cannot be JSON-encoded throw an exception
		if ($data === false)
		{
			throw new \InvalidArgumentException('Empty data cannot be encapsulated', 500);
		}

		// Make sure we have a valid key
		if (empty($key))
		{
			$key = $this->serverKey;
		}

		// Find the suitable handler and encode the data
		foreach ($this->handlers as $handler)
		{
			if ($handler->isSupported($encapsulation))
			{
				return $handler->encode($key, $data);
			}
		}

		// If the data cannot be encoded throw an exception
		$format = print_r($encapsulation, true);
		throw new \RuntimeException("Data cannot be encapsulated in the requested format ($format)", 500);
	}

	/**
	 * Initialises the encapsulation handlers
	 *
	 * @return  void
	 */
	protected function initialiseHandlers()
	{
		// Reset the arrays
		$this->handlers = array();
		$this->encapsulations = array();

		// Look all files in the Encapsulation handlers' directory
		$dh = new \DirectoryIterator(__DIR__ . '/Encapsulation');

		/** @var \DirectoryIterator $entry */
		foreach ($dh as $entry)
		{
			$fileName = $entry->getFilename();

			// Ignore non-PHP files
			if (substr($fileName, -4) != '.php')
			{
				continue;
			}

			// Ignore the Base class
			if ($fileName == 'Base.php')
			{
				continue;
			}

			// Get the class name
			$className = '\\Akeeba\\Backup\\Site\\Model\\Json\\Encapsulation\\' . substr($fileName, 0, -4);

			// Check if the class really exists
			if (!class_exists($className, true))
			{
				continue;
			}

			/** @var EncapsulationInterface $o */
			$o = new $className;
			$info = $o->getInformation();
			$this->encapsulations[$info['code']] = $info;
			$this->handlers[] = $o;
		}
	}
}
