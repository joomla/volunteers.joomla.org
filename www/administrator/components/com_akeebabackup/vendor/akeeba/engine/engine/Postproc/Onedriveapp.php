<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
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

namespace Akeeba\Engine\Postproc;

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\OneDriveApp as ConnectorOneDrive;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;

/**
 * OneDrive (custom Azure AD application) post-processing engine.
 *
 * @deprecated Legacy OneDrive integration tied to a per-user custom OAuth2 application. Superseded by the
 *             Onedrivebusiness engine using the modern Microsoft Graph API. Retained only for backwards compatibility.
 */
class Onedriveapp extends Onedrivebusiness
{
	/**
	 * The name of the OAuth2 callback method in the parent window (the configuration page)
	 *
	 * @var   string
	 */
	protected $callbackMethod = 'akeeba_onedriveapp_oauth_callback';

	/**
	 * The key in Akeeba Engine's settings registry for this post-processing method
	 *
	 * @var   string
	 */
	protected $settingsKey = 'onedriveapp';

	public function __construct()
	{
		parent::__construct();

		// This OneDrive integration does not allow the getDrives method.
		array_pop($this->allowedCustomAPICallMethods);
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$access_token  = trim($config->get('engine.postproc.' . $this->settingsKey . '.access_token', ''));
		$refresh_token = trim($config->get('engine.postproc.' . $this->settingsKey . '.refresh_token', ''));

		$this->isChunked  = $config->get('engine.postproc.' . $this->settingsKey . '.chunk_upload', true);
		$this->chunkSize  = $config->get('engine.postproc.' . $this->settingsKey . '.chunk_upload_size', 10) * 1024 * 1024;
		$defaultDirectory = rtrim($config->get('engine.postproc.' . $this->settingsKey . '.directory', ''), '/');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Sanity checks
		if (empty($refresh_token))
		{
			throw new BadConfiguration('You have not linked Akeeba Backup with your OneDrive account');
		}

		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		// Fix the directory name, if required
		$this->directory = empty($this->directory) ? '' : $this->directory;
		$this->directory = trim($this->directory);
		$this->directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($this->directory), '/');
		$this->directory = Factory::getFilesystemTools()->replace_archive_name_variables($this->directory);
		$config->set('volatile.postproc.directory', $this->directory);

		// Get Download ID
		$dlid = Platform::getInstance()->get_platform_configuration_option('update_dlid', '');

		if (empty($dlid))
		{
			throw new BadConfiguration('You must enter your Download ID in the application configuration before using the “Upload to OneDrive” feature.');
		}

		$connector = new ConnectorOneDrive($access_token, $refresh_token, $dlid);

		// Validate the tokens
		Factory::getLog()->debug(__METHOD__ . " - Validating the OneDrive tokens");
		$pingResult = $connector->ping();

		// Save new configuration if there was a refresh
		if ($pingResult['needs_refresh'])
		{
			Factory::getLog()->debug(__METHOD__ . " - OneDrive tokens were refreshed");
			$config->set('engine.postproc.' . $this->settingsKey . '.access_token', $pingResult['access_token'], false);
			$config->set('engine.postproc.' . $this->settingsKey . '.refresh_token', $pingResult['refresh_token'], false);

			Platform::getInstance()->save_configuration($config->activeProfile);
		}

		return $connector;
	}

	protected function getOAuth2HelperUrl()
	{
		return ConnectorOneDrive::helperUrl;
	}
}