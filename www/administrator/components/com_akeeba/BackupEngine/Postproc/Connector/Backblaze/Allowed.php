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
 * An immutable object which contains the 'allowed' key information returned by Backblaze b2_authorize_account API
 * method.
 *
 * @see  https://www.backblaze.com/b2/docs/b2_authorize_account.html
 *
 * @property-read  string $bucketId     The ID of the bucket we are limited to. Empty if we are not limited to a bucket.
 * @property-read  string $bucketName   The name of the bucket we are limited to. Empty if we are not limited to a bucket.
 * @property-read  array  $capabilities An array containing one or more of listKeys, writeKeys, deleteKeys, listBuckets, writeBuckets, deleteBuckets, listFiles, readFiles, shareFiles, writeFiles, and deleteFiles
 * @property-read  string $namePrefix   The prefix inside the bucket we are allowed to write to
 */
class Allowed
{
	/**
	 * The ID of the bucket we are limited to. Empty if we are not limited to a bucket.
	 *
	 * @var string
	 */
	private $bucketId;

	/**
	 * The name of the bucket we are limited to. Empty if we are not limited to a bucket.
	 *
	 * @var string
	 */
	private $bucketName;

	/**
	 * An array containing one or more of listKeys, writeKeys, deleteKeys, listBuckets, writeBuckets, deleteBuckets, listFiles, readFiles, shareFiles, writeFiles, and deleteFiles
	 *
	 * @var array
	 */
	private $capabilities = [];

	/**
	 * The prefix inside the bucket we are allowed to write to
	 *
	 * @var string
	 */
	private $namePrefix;

	/**
	 * Construct an Allowed object from a key-value array
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
	 * Are we granted a specific capability by the API? recommended to use the can*() methods instead.
	 *
	 * @param   string  $cap  The capability to check
	 *
	 * @return  bool
	 */
	public function hasCapability($cap)
	{
		if (!is_array($this->capabilities))
		{
			return false;
		}

		return in_array($cap, $this->capabilities);
	}

	/**
	 * Are we allowed to list keys?
	 *
	 * @return  bool
	 */
	public function canListKeys()
	{
		return $this->hasCapability('listKeys');
	}

	/**
	 * Are we allowed to write keys?
	 *
	 * @return  bool
	 */
	public function canWriteKeys()
	{
		return $this->hasCapability('writeKeys');
	}

	/**
	 * Are we allowed to delete keys?
	 *
	 * @return  bool
	 */
	public function canDeleteKeys()
	{
		return $this->hasCapability('deleteKeys');
	}

	/**
	 * Are we allowed to list buckets?
	 *
	 * @return  bool
	 */
	public function canListBuckets()
	{
		return $this->hasCapability('listBuckets');
	}

	/**
	 * Are we allowed to write (create new) buckets?
	 *
	 * @return  bool
	 */
	public function canWriteBuckets()
	{
		return $this->hasCapability('writeBuckets');
	}

	/**
	 * Are we allowed to delete buckets?
	 *
	 * @return  bool
	 */
	public function canDeleteBuckets()
	{
		return $this->hasCapability('deleteBuckets');
	}

	/**
	 * Are we allowed to list files?
	 *
	 * @return  bool
	 */
	public function canListFiles()
	{
		return $this->hasCapability('listFiles');
	}

	/**
	 * Are we allowed to read files?
	 *
	 * @return  bool
	 */
	public function canReadFiles()
	{
		return $this->hasCapability('readFiles');
	}

	/**
	 * Are we allowed to share files?
	 *
	 * @return  bool
	 */
	public function canShareFiles()
	{
		return $this->hasCapability('shareFiles');
	}

	/**
	 * Are we allowed to write to files?
	 *
	 * @return  bool
	 */
	public function canWriteFiles()
	{
		return $this->hasCapability('writeFiles');
	}

	/**
	 * Are we allowed to delete files?
	 *
	 * @return  bool
	 */
	public function canDeleteFiles()
	{
		return $this->hasCapability('deleteFiles');
	}

	/**
	 * Are we allowed to access the bucket in question?
	 *
	 * @param   string  $bucket  The bucket you need to know if we are allowed to access
	 *
	 * @return  bool
	 */
	public function isBucketAllowed($bucket)
	{
		if (empty($this->bucketName))
		{
			return true;
		}

		return $this->bucketName === $bucket;
	}

	/**
	 * Are we allowed to access files / folders with the given prefix?
	 *
	 * @param   string  $prefix  Path to a file or folder you want to test. Whole or partial (the leading part
	 *                           must be provided in this case)
	 *
	 * @return  bool
	 */
	public function isPrefixAllowed($prefix)
	{
		if (empty($this->namePrefix))
		{
			return true;
		}

		return strpos(ltrim($prefix, '/'), $this->namePrefix) === 0;
	}
}
