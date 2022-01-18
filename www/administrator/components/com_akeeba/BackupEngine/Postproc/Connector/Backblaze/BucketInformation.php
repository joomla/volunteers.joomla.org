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
 * An immutable object which contains the information returned by Backblaze b2_list_buckets API method
 *
 * @see  https://www.backblaze.com/b2/docs/b2_list_buckets.html
 *
 * @property-read  string accountId       Backblaze account ID
 * @property-read  string bucketId        The ID of the bucket (you will need to use this in the API)
 * @property-read  array  bucketInfo      User data stored in this bucket
 * @property-read  string bucketName      The unique name of the bucket, i.e. what the user calls the bucket by
 * @property-read  string bucketType      allPublic, allPrivate, snapshot (possibly more values in the future)
 * @property-read  array  lifecycleRules  List of lifecycle rules for this bucket
 */
class BucketInformation
{
	/** @var  string  Backblaze account ID */
	private $accountId;

	/** @var  string  The ID of the bucket (you will need to use this in the API) */
	private $bucketId;

	/** @var  array  User data stored in this bucket */
	private $bucketInfo;

	/** @var  string  The unique name of the bucket, i.e. what the user calls the bucket by */
	private $bucketName;

	/** @var  string  allPublic, allPrivate, snapshot (possibly more values in the future) */
	private $bucketType;

	/** @var  string  List of lifecycle rules for this bucket */
	private $lifecycleRules;

	/**
	 * Construct an BucketInformation object from a key-value array
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
