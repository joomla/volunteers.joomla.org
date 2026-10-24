<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

/**
 * OAuth2 Helper token response
 *
 * @since   9.9.1
 */
class TokenResponse implements \ArrayAccess
{
	private ?string $accessToken = null;

	private ?string $refreshToken = null;

	/** @inheritDoc */
	public function offsetExists($offset)
	{
		return isset($this->{$offset});
	}

	/** @inheritDoc */
	public function offsetGet($offset)
	{
		return $this->{$offset} ?? null;
	}

	/** @inheritDoc */
	public function offsetSet($offset, $value)
	{
		if ($this->offsetExists($offset))
		{
			return;
		}

		$this->{$offset} = $value;
	}

	/** @inheritDoc */
	public function offsetUnset($offset)
	{
		throw new \BadMethodCallException(
			sprintf(
				'You cannot unset an offset in %s',
				__CLASS__
			)
		);
	}

	/**
	 * Casts the data into a plain array
	 *
	 * @return  array
	 * @since   9.9.1
	 */
	public function toArray()
	{
		return [
			'accessToken'  => $this->accessToken,
			'refreshToken' => $this->refreshToken,
		];
	}
}