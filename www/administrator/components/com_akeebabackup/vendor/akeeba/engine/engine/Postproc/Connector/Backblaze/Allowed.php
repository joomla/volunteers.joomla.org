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
 * An immutable object which contains the 'allowed' key information returned by Backblaze b2_authorize_account API
 * method.
 *
 * @see  https://www.backblaze.com/b2/docs/b2_authorize_account.html
 *
 * @property-read  string $bucketId     The ID of the bucket we are limited to. Empty if we are not limited to a bucket.
 * @property-read  string $bucketName   The name of the bucket we are limited to. Empty if we are not limited to a bucket.
 * @property-read  array  $buckets      v4: list of {bucketId, bucketName} objects the key may access.
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
	 * v4: list of {bucketId, bucketName} objects the key may access. Empty means unrestricted.
	 *
	 * @var array
	 */
	private $buckets = [];

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

		// An unrestricted key omits buckets entirely, and the API may send it as an explicit null. Callers iterate this,
		// so it must always be an array.
		if (!is_array($this->buckets))
		{
			$this->buckets = [];
		}

		// v4 replaces bucketId/bucketName with a buckets[] array for multi-bucket keys. The live API keys each entry
		// id/name; earlier documentation used bucketId/bucketName. Normalise every entry to {bucketId, bucketName} so
		// all read sites see a single, consistent shape regardless of which key spelling the API sends.
		if (!empty($this->buckets))
		{
			$this->buckets = array_map(
				static function ($entry) {
					$entry = (array) $entry;

					return [
						'bucketId'   => $entry['bucketId'] ?? $entry['id'] ?? '',
						'bucketName' => $entry['bucketName'] ?? $entry['name'] ?? '',
					];
				},
				array_values($this->buckets)
			);

			// Seed the scalar fields so single-bucket callers keep working. A bucket that has been deleted comes back
			// with a null name; seeding from such an entry would leave bucketName empty and shadow a perfectly good
			// named bucket sitting behind it in the list. Prefer the first entry we can actually name.
			if (empty($this->bucketId))
			{
				$named = array_values(
					array_filter(
						$this->buckets,
						static function ($entry) {
							return $entry['bucketName'] !== '';
						}
					)
				);

				$seed = $named[0] ?? $this->buckets[0];

				$this->bucketId   = $seed['bucketId'];
				$this->bucketName = $seed['bucketName'];
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
	 * Is a property set, and not null?
	 *
	 * Without this, isset() and empty() on these properties go looking for __isset(), do not find it, and conclude the
	 * property is unset — so empty($allowed->bucketName) came back true no matter what the bucket was actually called.
	 * The properties are private, so an outside caller never reaches them directly and PHP always routes through the
	 * magic methods. __get() alone is not enough.
	 *
	 * @param   string  $name  The property name being tested
	 *
	 * @return  bool
	 */
	public function __isset($name)
	{
		return property_exists($this, $name) && !is_null($this->$name);
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
		// v4 multi-bucket keys: check against the full buckets list.
		if (!empty($this->buckets))
		{
			$hasUnnamed = false;

			foreach ($this->buckets as $entry)
			{
				$entry = (array) $entry;
				$name  = $entry['bucketName'] ?? '';

				if ($name === '')
				{
					$hasUnnamed = true;

					continue;
				}

				if ($name === $bucket)
				{
					return true;
				}
			}

			// A bucket the API would not name for us (a deleted one) could be the very bucket we were asked about. We
			// cannot prove it is disallowed, and this check is only a courtesy that turns a remote 401 into a legible
			// error — Backblaze enforces the restriction regardless. Defer to the API rather than block a valid upload.
			return $hasUnnamed;
		}

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
