<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

defined('_JEXEC') || die;

use Composer\CaBundle\CaBundle;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;

abstract class AbstractProvider implements ProviderInterface
{
	protected string $tokenEndpoint = '';

	protected string $engineNameForHumans = '';

	public function getEngineNameForHumans(): string
	{
		return $this->engineNameForHumans;
	}

	public final function handleResponse(Input $input): TokenResponse
	{
		$this->checkConfiguration();

		[$id, $secret] = $this->getIdAndSecret();

		$code = $input->getRaw('code');

		if (!$code)
		{
			throw new OAuth2Exception('no_code', 'No code has been provided in the URL.');
		}

		$query = http_build_query($this->getResponseCustomFields($input), '', '&');
		$ch    = curl_init($this->tokenEndpoint);

		$options = [
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_VERBOSE        => true,
			CURLOPT_HEADER         => false,
			CURLINFO_HEADER_OUT    => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CAINFO         => CaBundle::getBundledCaBundlePath(),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => $query,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/x-www-form-urlencoded',
			],
		];

		curl_setopt_array($ch, $options);

		// Get the tokens
		$response = curl_exec($ch);
		$errNo    = curl_errno($ch);
		$error    = curl_error($ch);

		if (version_compare(PHP_VERSION, '8.5.0', 'lt'))
		{
			curl_close($ch);
		}

		// Did cURL die?
		if ($errNo)
		{
			throw new OAuth2Exception(
				'curl_error', <<< HTML
An error occurred communicating with $this->engineNameForHumans. Technical information:<br/><br/>
Error Number: $errNo<br/>
Error Description: $error<br/>
HTML
			);
		}

		// Decode the response
		$result = @json_decode($response, true);

		// Did we receive invalid JSON?
		if (!$result)
		{
			throw new OAuth2Exception(
				'invalid_json',
				sprintf("%s failed to response with a valid token. Please try again later.", $this->engineNameForHumans)
			);
		}

		// Do we have an error reported by the remote endpoint?
		if (isset($result['error']))
		{
			$error            = $result['error'];
			$errorUri         = $result['error_uri'] ?? null;
			$errorDescription = $result['error_uri'] ?? null;

			if ($errorUri)
			{
				throw new OAuth2UriException($errorUri);
			}

			throw new OAuth2Exception($error, $errorDescription);
		}

		$ret                 = new TokenResponse();
		$ret['accessToken']  = $result['access_token'] ?? '';
		$ret['refreshToken'] = $result['refresh_token'] ?? '';

		return $ret;
	}

	public final function handleRefresh(Input $input): TokenResponse
	{
		$refreshToken = $input->getRaw('refresh_token');
		$this->checkConfiguration();

		if (empty($refreshToken))
		{
			throw new OAuth2Exception(
				'no_refresh_token', 'A refresh token was not provided. Operation aborted.'
			);
		}

		[$id, $secret] = $this->getIdAndSecret();

		// Prepare the request to get the tokens
		$query = http_build_query($this->getRefreshCustomFields($input), '', '&');
		$ch    = curl_init($this->tokenEndpoint);

		$options = [
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_VERBOSE        => true,
			CURLOPT_HEADER         => false,
			CURLINFO_HEADER_OUT    => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CAINFO         => CaBundle::getBundledCaBundlePath(),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => $query,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/x-www-form-urlencoded',
			],
		];

		curl_setopt_array($ch, $options);

		// Get the tokens
		$response = curl_exec($ch);
		$errNo    = curl_errno($ch);
		$error    = curl_error($ch);

		if (version_compare(PHP_VERSION, '8.5.0', 'lt'))
		{
			curl_close($ch);
		}

		// Did cURL die?
		if ($errNo)
		{
			throw new OAuth2Exception(
				'curl_error', sprintf(
					"An error occurred refreshing the token. Error Number: %s -- Error Description: %s", $errNo, $error
				)
			);
		}

		// Decode the response
		$result = @json_decode($response, true);

		// Did we receive invalid JSON?
		if (!$result)
		{
			throw new OAuth2Exception(
				'invalid_json', sprintf(
					"%s failed to respond with a valid token to our token refresh request.", $this->engineNameForHumans
				)
			);
		}

		$ret                 = new TokenResponse();
		$ret['accessToken']  = $result['access_token'] ?? '';
		$ret['refreshToekn'] = $result['refresh_token'] ?? '';

		return $ret;
	}

	protected function getResponseCustomFields(Input $input): array
	{
		[$id, $secret] = $this->getIdAndSecret();

		$code = $input->getRaw('code');

		return [
			'code'          => $code,
			'client_id'     => $id,
			'client_secret' => $secret,
			'grant_type'    => 'authorization_code',
		];
	}

	protected function getRefreshCustomFields(Input $input): array
	{
		$refreshToken = $input->getRaw('refresh_token');
		[$id, $secret] = $this->getIdAndSecret();

		return [
			'refresh_token' => $refreshToken,
			'client_id'     => $id,
			'client_secret' => $secret,
			'grant_type'    => 'refresh_token',
		];
	}

	protected final function getEngineName(): string
	{
		$parts = explode('\\', rtrim(get_class($this), '\\'));
		$name  = array_pop($parts);

		if (str_ends_with($name, 'Engine'))
		{
			$name = substr($name, 0, -6);
		}

		return strtolower($name);
	}

	protected final function checkConfiguration(): void
	{
		$engine = $this->getEngineName();

		// Is the engine enabled?
		$cParams = ComponentHelper::getParams('com_akeebabackup');

		if ($cParams->get('oauth2_client_' . $engine, 0) == 0)
		{
			throw new OAuth2Exception('no_access', Text::_('JERROR_ALERTNOAUTHOR'));
		}

		$id     = $cParams->get($engine . '_client_id', null);
		$secret = $cParams->get($engine . '_client_secret', null);

		if (empty($id) || empty($secret))
		{
			throw new OAuth2Exception('no_access', Text::_('JERROR_ALERTNOAUTHOR'));
		}
	}

	protected final function getIdAndSecret(): array
	{
		$cParams = ComponentHelper::getParams('com_akeebabackup');
		$engine  = $this->getEngineName();
		$id      = $cParams->get($engine . '_client_id', null);
		$secret  = $cParams->get($engine . '_client_secret', null);

		return [$id, $secret];
	}

	protected final function getUri(string $task = 'step1')
	{
		$uri = rtrim(Uri::base(), '/');

		if (str_ends_with($uri, '/administrator'))
		{
			$uri = substr($uri, 0, -14);
		}
		elseif (str_ends_with($uri, '/api'))
		{
			$uri = substr($uri, 0, -4);
		}

		return sprintf(
			"%s/index.php?option=com_akeebabackup&view=oauth2&task=step2&format=raw&engine=%s", $uri,
			$this->getEngineName()
		);
	}
}