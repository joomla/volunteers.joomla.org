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
 * Generic OAuth2 Helper error
 *
 * @since   9.9.1
 */
class OAuth2Exception extends RuntimeException
{
	public function __construct(string $error, ?string $description = "", ?Throwable $previous = null)
	{
		$description = $description ?: $this->getDefaultErrorDescription($error);
		$message     = sprintf('%s: %s', $error, $description);

		parent::__construct($message, 500, $previous);
	}

	private function getDefaultErrorDescription(string $error): string
	{
		switch ($error)
		{
			case 'invalid_request':
				return 'The request sent to the storage provider is invalid. Please check your Client ID and Client Secret in Akeeba Backup\'s configuration. Also, make sure the callback URI is set up correctly on the remote storage provider. If necessary, relink Akeeba Backup with the remote storage provider';

			case 'invalid_client':
				return 'The configured Client ID is incorrect.';

			case 'invalid_grant':
				return 'The grant type is invalid, the code has already been used (you tried to refresh the page), you failed to log into the remote storage provider, or declined to give authorisation.';

			case 'unauthorized_client':
				return 'Your account with the remote storage provider is not allowed to be linked with your API application. Please check your API application configuration with the remote storage provider.';

			case 'unsupported_grant_type':
				return 'The grant type requested is not supported by the remote storage provider. Please check your API application configuration with the remote storage provider.';

			case 'invalid_scope':
				return 'The authentication scope requested is not supported by the remote storage provider. Please check your API application configuration with the remote storage provider.';

			default:
				return 'A generic error occurred, which we do not have any further information for.';
		}
	}

}