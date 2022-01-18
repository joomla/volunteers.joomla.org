<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\Backblaze;

defined('AKEEBAENGINE') || die();

use DomainException;

/**
 * An immutable object which contains the information returned by BackBlaze when uploading files.
 *
 * @see  https://www.backblaze.com/b2/docs/b2_authorize_account.html
 *
 * @property-read  string fileId           The unique identifier for this version of this file.
 * @property-read  string fileName         The name of this file
 * @property-read  string accountId        Backblaze account ID
 * @property-read  string bucketId         The bucket that the file is in
 * @property-read  string contentLength    The number of bytes stored in the file.
 * @property-read  string contentSha1      The SHA1 of the bytes stored in the file.
 * @property-read  string contentType      The MIME type of the file.
 * @property-read  string fileInfo         The custom information that was uploaded with the file.
 * @property-read  string action           Always "upload".
 * @property-read  string uploadTimestamp  This is a UTC time when this file was uploaded.
 * @property-read  string partNumber       Part number, when this is a part of a multipart upload, not a single file
 */
class FileInformation
{
	private $fileId;
	private $fileName;
	private $accountId;
	private $bucketId;
	private $contentLength;
	private $contentSha1;
	private $contentType;
	private $fileInfo;
	private $action;
	private $uploadTimestamp;
	private $partNumber;

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
}
