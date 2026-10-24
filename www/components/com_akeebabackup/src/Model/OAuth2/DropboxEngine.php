<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

use Joomla\Input\Input;

class DropboxEngine extends AbstractProvider implements ProviderInterface
{
	protected string $tokenEndpoint = 'https://api.dropboxapi.com/1/oauth2/token';

	protected string $engineNameForHumans = 'Dropbox';

	public function getAuthenticationUrl(): string
	{
		$this->checkConfiguration();

		[$id, $secret] = $this->getIdAndSecret();

		$params = [
			'client_id'         => $id,
			'response_type'     => 'code',
			'redirect_uri'      => $this->getUri('step2'),
			'scope'             => implode(
				' ', [
				'account_info.read',
				'files.metadata.read',
				'files.content.write',
				'files.content.read',
				'team_data.member',
			]
			),
			'token_access_type' => 'offline',
		];

		return 'https://www.dropbox.com/1/oauth2/authorize?' . http_build_query($params);
	}

	protected function getResponseCustomFields(Input $input): array
	{
		$fields = parent::getResponseCustomFields($input);

		unset($fields['client_id']);
		unset($fields['client_secret']);

		$fields['redirect_uri'] = $this->getUri('step2');

		return $fields;
	}

	protected function getRefreshCustomFields(Input $input): array
	{
		$fields = parent::getRefreshCustomFields($input);

		unset($fields['client_id']);
		unset($fields['client_secret']);

		return $fields;
	}
}