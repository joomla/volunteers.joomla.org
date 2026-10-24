<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

class BoxEngine extends AbstractProvider implements ProviderInterface
{
	protected string $tokenEndpoint = 'https://api.box.com/oauth2/token';

	protected string $engineNameForHumans = 'Box.com';

	public function getAuthenticationUrl(): string
	{
		$this->checkConfiguration();

		[$id, $secret] = $this->getIdAndSecret();

		$params = [
			'response_type' => 'code',
			'client_id'     => $id,
			'redirect_uri'  => $this->getUri('step2'),
		];

		return 'https://account.box.com/api/oauth2/authorize?' . http_build_query($params);
	}
}