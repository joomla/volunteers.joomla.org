<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Model\OAuth2;

use Joomla\Input\Input;

defined('_JEXEC') || die;

/**
 * OAuth2 Helper provider interface
 *
 * @since    9.9.1
 */
interface ProviderInterface
{
	/**
	 * Get the URL to redirect to for the first authentication step (consent screen).
	 *
	 * @return  string
	 * @since   9.9.1
	 */
	public function getAuthenticationUrl(): string;

	/**
	 * Handles the second step of the authentication (exchange code for tokens)
	 *
	 * @param   Input  $input  The raw application input object
	 *
	 * @return  TokenResponse
	 * @since   9.9.1
	 */
	public function handleResponse(Input $input): TokenResponse;

	/**
	 * Handles exchanging a refresh token for an access token
	 *
	 * @param   Input  $input  The raw application input object
	 *
	 * @return  TokenResponse
	 * @since   9.9.1
	 */
	public function handleRefresh(Input $input): TokenResponse;

	public function getEngineNameForHumans(): string;
}