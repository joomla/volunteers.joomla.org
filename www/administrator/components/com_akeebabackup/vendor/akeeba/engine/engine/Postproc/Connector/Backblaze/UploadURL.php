<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc\Connector\Backblaze;

defined('AKEEBAENGINE') || die();

use DomainException;

/**
 * An immutable object which contains the information returned by BackBlaze when uploading files, single- or multipart.
 *
 * @see  https://www.backblaze.com/b2/docs/b2_authorize_account.html
 *
 * @property-read  string fileId              The file ID, used for multipart uploads
 * @property-read  string bucketId            The bucket ID, used for single part uploads
 * @property-read  string uploadUrl           The URL that can be used to upload files to this bucket / file
 * @property-read  string authorizationToken  The authorizationToken that must be used when uploading files to this bucket
 */
class UploadURL
{
	private $fileId;
	private $bucketId;
	private $uploadUrl;
	private $authorizationToken;

	/**
	 * Construct an object from a key-value array
	 *
	 * @param   array  $data  The raw data array returned by the Backblaze B2 API
	 */
	public function __construct(array $data)
	{
		if (empty($data))
		{
			return;
		}

		foreach ($data as $key => $value)
		{
			if (property_exists($this, $key))
			{
				$this->$key = $value;
			}
		}
	}

	/**
	 * Magic getter, channels the private property values. This lets the object have immutable, publicly accessible
	 * properties.
	 *
	 * @param   string  $name  The property name being read
	 *
	 * @return  mixed
	 *
	 * @throws  DomainException  If you ask for a property that's not there
	 */
	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new DomainException(sprintf("Property %s does not exist in class %s", $name, __CLASS__));
	}

	/**
	 * Exports the data as an array which can be used with __construct to reconstruct this object. The array data is
	 * easier to serialize since they can be converted to JSON, for example.
	 *
	 * @return  array
	 */
	public function toArray()
	{
		return [
			'fileId'             => $this->fileId,
			'bucketId'           => $this->bucketId,
			'uploadUrl'          => $this->uploadUrl,
			'authorizationToken' => $this->authorizationToken,
		];
	}
}
