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

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\OneDriveBusiness as ConnectorOneDrive;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Awf\Text\Text;
use Exception;
use Joomla\CMS\Language\Text as JText;

class Onedrivebusiness extends Onedrive
{
	/**
	 * The name of the OAuth2 callback method in the parent window (the configuration page)
	 *
	 * @var   string
	 */
	protected $callbackMethod = 'akeeba_onedrivebusiness_oauth_callback';

	/**
	 * The key in Akeeba Engine's settings registry for this post-processing method
	 *
	 * @var   string
	 */
	protected $settingsKey = 'onedrivebusiness';

	public function __construct()
	{
		parent::__construct();

		$this->allowedCustomAPICallMethods[] = 'getDrives';
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

		$driveId = $config->get('engine.postproc.onedrivebusiness.drive', null);
		$driveId = $driveId ?: null;

		$connector = new ConnectorOneDrive(
			$access_token,
			$refresh_token,
			$dlid,
			$driveId,
			$config->get('engine.postproc.onedrivebusiness.oauth2_refresh')
		);

		// Restore the persisted access-token expiry so the connector can refresh proactively (before the token lapses)
		// across stepped backup runs, instead of only reacting after a request has already failed.
		$connector->setTokenExpiration((int) $config->get('engine.postproc.' . $this->settingsKey . '.token_expiration', 0));

		// Validate the tokens
		Factory::getLog()->debug(__METHOD__ . " - Validating the OneDrive tokens");
		$pingResult = $connector->ping();

		// Save new configuration if there was a refresh
		if ($pingResult['needs_refresh'])
		{
			Factory::getLog()->debug(__METHOD__ . " - OneDrive tokens were refreshed");
			$config->set('engine.postproc.' . $this->settingsKey . '.access_token', $pingResult['access_token'], false);
			$config->set('engine.postproc.' . $this->settingsKey . '.refresh_token', $pingResult['refresh_token'], false);
			$config->set('engine.postproc.' . $this->settingsKey . '.token_expiration', $pingResult['token_expiration'] ?? 0, false);

			Platform::getInstance()->save_configuration($config->activeProfile);
		}

		return $connector;
	}

	/**
	 * Used by the interface to display a list of drives to choose from.
	 *
	 * @param   array  $params
	 *
	 * @return  array
	 *
	 * @throws  Exception
	 */
	public function getDrives($params = [])
	{
		// Get the default item (the personal Drive)
		$baseItem = 'Drive (OneDrive Personal)';

		if (class_exists('\Joomla\CMS\Language\Text'))
		{
			$baseItem = JText::_('COM_AKEEBA_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL');

			if ($baseItem === 'COM_AKEEBA_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL')
			{
				$baseItem = JText::_('COM_AKEEBABACKUP_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL');
			}
		}

		if (class_exists('\Awf\Text\Text'))
		{
			$baseItem = \Awf\Text\Text::_('COM_AKEEBA_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL');

			if ($baseItem === 'COM_AKEEBA_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL')
			{
				$baseItem = \Awf\Text\Text::_('COM_AKEEBABACKUP_CONFIG_ONEDRIVE_DRIVE_OPT_PERSONAL');
			}
		}

		$items = [
			'' => $baseItem,
		];

		// Try to get a list of Team Drives
		try
		{
			$this->configOverrides = $params;
			/** @var \Akeeba\Engine\Postproc\Connector\OneDriveBusiness $connector */
			$connector = $this->getConnector(true);
			$connector->ping();

			$items = array_merge($items, $connector->getDrives());
		}
		catch (Exception $e)
		{
			// No worries, the user hasn't configured Google Drive correctly just yet.
		}

		$ret = [];

		foreach ($items as $k => $v)
		{
			$ret[] = [$k, $v];
		}

		return $ret;
	}

	protected function getOAuth2HelperUrl()
	{
		$config           = Factory::getConfiguration();
		$oauth2HelperType = $config->get('engine.postproc.onedrivebusiness.oauth2_type', 'akeeba');

		switch ($oauth2HelperType)
		{
			case 'custom':
				return $config->get('engine.postproc.onedrivebusiness.oauth2_helper', '');

			default:
				return ConnectorOneDrive::helperUrl;
		}
	}

	/** @inheritDoc */
	protected function mustChunk(int $fileSize): bool
	{
		return $fileSize > 4194304;
	}

	/** @inheritDoc */
	protected function mustSingeUpload(int $fileSize): bool
	{
		return ($fileSize <= $this->chunkSize) || ($fileSize <= 4194304);
	}
}
