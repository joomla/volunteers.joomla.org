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
 * BLOB entry
 *
 * @property string  $Container       Container name
 * @property string  $Name            Name
 * @property string  $Etag            Etag
 * @property string  $LastModified    Last modified date
 * @property string  $Url             Url
 * @property int     $Size            Size
 * @property string  $ContentType     Content Type
 * @property string  $ContentEncoding Content Encoding
 * @property string  $ContentLanguage Content Language
 * @property boolean $IsPrefix        Is Prefix?
 * @property array   $Metadata        Key/value pairs of meta data
 *
 * @since 9.2.1
 */
class Blob
{
	/**
	 * Data
	 *
	 * @var   array
	 * @since 9.2.1
	 */
	protected $_data = null;

	/**
	 * Constructor
	 *
	 * @param   string  $containerName    Container name
	 * @param   string  $name             Name
	 * @param   string  $etag             Etag
	 * @param   string  $lastModified     Last modified date
	 * @param   string  $url              Url
	 * @param   int     $size             Size
	 * @param   string  $contentType      Content Type
	 * @param   string  $contentEncoding  Content Encoding
	 * @param   string  $contentLanguage  Content Language
	 * @param   bool    $isPrefix         Is Prefix?
	 * @param   array   $metadata         Key/value pairs of meta data
	 */
	public function __construct(
		string $containerName, string $name, string $etag, string $lastModified, string $url = '', int $size = 0,
		string $contentType = '', string $contentEncoding = '', string $contentLanguage = '',
		bool   $isPrefix = false, array $metadata = []
	)
	{
		$this->_data = [
			'container'       => $containerName,
			'name'            => $name,
			'etag'            => $etag,
			'lastmodified'    => $lastModified,
			'url'             => $url,
			'size'            => $size,
			'contenttype'     => $contentType,
			'contentencoding' => $contentEncoding,
			'contentlanguage' => $contentLanguage,
			'isprefix'        => $isPrefix,
			'metadata'        => $metadata,
		];
	}

	/**
	 * Magic overload for getting properties
	 *
	 * @param   string  $name  Name of the property
	 *
	 * @throws  Exception
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
	 * @throws  Exception
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
