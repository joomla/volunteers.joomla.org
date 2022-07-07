<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern\Blob;

defined('AKEEBAENGINE') || die();

use Exception;

/**
 * @property  string  $Name          Name of the container
 * @property  string  $Etag          Etag of the container
 * @property  string  $LastModified  Last modified date of the container
 * @property  array   $Metadata      Key/value pairs of meta data
 *
 * @since    9.2.1
 */
class Container
{
	/**
	 * Data
	 *
	 * @var array
	 */
	protected $_data = null;

	/**
	 * Constructor
	 *
	 * @param   string  $name          Name
	 * @param   string  $etag          Etag
	 * @param   string  $lastModified  Last modified date
	 * @param   array   $metadata      Key/value pairs of meta data
	 */
	public function __construct(string $name, string $etag, string $lastModified, array $metadata = [])
	{
		$this->_data = [
			'name'         => $name,
			'etag'         => $etag,
			'lastmodified' => $lastModified,
			'metadata'     => $metadata,
		];
	}

	/**
	 * Magic overload for getting properties
	 *
	 * @param   string  $name  Name of the property
	 *
	 * @throws Exception
	 */
	public function __get($name)
	{
		if (array_key_exists(strtolower($name), $this->_data))
		{
			return $this->_data[strtolower($name)];
		}

		throw new Exception("Unknown property: " . $name);
	}

	/**
	 * Magic overload for setting properties
	 *
	 * @param   string  $name   Name of the property
	 * @param   string  $value  Value to set
	 *
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		if (array_key_exists(strtolower($name), $this->_data))
		{
			$this->_data[strtolower($name)] = $value;

			return;
		}

		throw new Exception("Unknown property: " . $name);
	}
}
