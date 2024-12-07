<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc\Connector\Backblaze;

defined('AKEEBAENGINE') || die();

use DomainException;

/**
 * An immutable object which contains the information returned by Backblaze b2_authorize_account API method
 *
 * @see  https://www.backblaze.com/b2/docs/b2_authorize_account.html
 *
 * @property-read  string  accountId                Backblaze account ID
 * @property-read  string  authorizationToken       Temporary authorization token
 * @property-read  Allowed allowed                  Backblaze account ID
 * @property-read  string  apiUrl                   API URL for everything except download operations
 * @property-read  string  downloadUrl              API URL for download operations
 * @property-read  string  recommendedPartSize      Recommended part size, in bytes
 * @property-read  string  absoluteMinimumPartSize  Minimum possible part size, in bytes
 * @property-read  string  minimumPartSize          DEPRECATED: This field will always have the same value as recommendedPartSize.
 */
class AccountInformation
{
	/** @var  string  Minimum possible part size, in bytes */
	private $absoluteMinimumPartSize;

	/** @var  string  Backblaze account ID */
	private $accountId;

	/** @var  Allowed  An object describing what we are allowed to do with the current ID and key pair */
	private $allowed;

	/** @var  string  API URL for everything except download operations */
	private $apiUrl;

	/** @var  string  Temporary authorization token */
	private $authorizationToken;

	/** @var  string  API URL for download operations */
	private $downloadUrl;

	/** @var  string  DEPRECATED: Alias of recommendedPartSize */
	private $minimumPartSize;

	/** @var  string  Recommended part size, in bytes */
	private $recommendedPartSize;

	/** @var  int     This object is valid until this UNIX timestamp */
	private $validTo;

	/**
	 * Construct an AccountInformation object from a key-value array
	 *
	 * @param   array  $data  The raw data array returned by the Backblaze B2 API
	 */
	public function __construct(array $data)
	{
		// The authorization token is valid for up to 24 hours
		$this->validTo = time() + 86400;

		if (empty($data))
		{
			return;
		}

		foreach ($data as $key => $value)
		{
			if ($key == 'allowed')
			{
				$this->allowed = new Allowed($value);

				continue;
			}

			if (property_exists($this, $key))
			{
				$this->$key = $value;
			}
		}

		if (is_null($this->allowed))
		{
			$this->allowed = new Allowed([]);
		}

		if (empty($this->minimumPartSize))
		{
			$this->minimumPartSize = $this->recommendedPartSize;
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
	 * Is the authorization token still valid? We consider it valid for up to 23 hours since it was issued, to prevent
	 * the chance of the token timing out while we are trying to do an upload.
	 *
	 * @return  bool
	 */
	public function isValid()
	{
		$now     = time();
		$validTo = $this->validTo - 3600;

		return ($validTo >= $now);
	}
}
