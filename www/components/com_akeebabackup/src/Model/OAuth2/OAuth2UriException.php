<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

use RuntimeException;
use Throwable;

/**
 * OAuth2 Helper error redirecting to a URL
 *
 * @since   9.9.1
 */
class OAuth2UriException extends RuntimeException
{
	private string $url;

	public function __construct(string $url, ?Throwable $previous = null)
	{
		$message = sprintf('For more information please visit %s', $url);
		$this->url = $url;

		parent::__construct($message, 500, $previous);
	}

	public function getUrl(): string
	{
		return $this->url;
	}
}