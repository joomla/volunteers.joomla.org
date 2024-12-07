<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package     Akeeba\Engine\Postproc\Connector\AzureModern\Blob
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Akeeba\Engine\Postproc\Connector\AzureModern\Blob;

use Exception;

/**
 * @category   Microsoft
 * @package    Microsoft_WindowsAzure
 * @subpackage Storage
 * @copyright  Copyright (c) 2009, RealDolmen (http://www.realdolmen.com)
 * @license    http://phpazure.codeplex.com/license
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
 */
class Instance
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
	 * @param   string   $containerName    Container name
	 * @param   string   $name             Name
	 * @param   string   $etag             Etag
	 * @param   string   $lastModified     Last modified date
	 * @param   string   $url              Url
	 * @param   int      $size             Size
	 * @param   string   $contentType      Content Type
	 * @param   string   $contentEncoding  Content Encoding
	 * @param   string   $contentLanguage  Content Language
	 * @param   boolean  $isPrefix         Is Prefix?
	 * @param   array    $metadata         Key/value pairs of meta data
	 */
	public function __construct($containerName, $name, $etag, $lastModified, $url = '', $size = 0, $contentType = '', $contentEncoding = '', $contentLanguage = '', $isPrefix = false, $metadata = [])
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