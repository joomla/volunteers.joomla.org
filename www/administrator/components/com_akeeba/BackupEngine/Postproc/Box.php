<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\Box as ConnectorBox;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Box extends Base
{
	/**
	 * The retry count of this file (allow up to 2 retries after the first upload failure)
	 *
	 * @var int
	 */
	protected $tryCount = 0;

	/**
	 * The currently configured directory
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * The name of the OAuth2 callback method in the parent window (the configuration page)
	 *
	 * @var   string
	 */
	protected $callbackMethod = 'akconfig_box_oauth_callback';

	/**
	 * The key in Akeeba Engine's settings registry for this post-processing method
	 *
	 * @var   string
	 */
	protected $settingsKey = 'box';

	public function __construct()
	{
		$this->supportsDownloadToBrowser = false;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function oauthCallback(array $params)
	{
		$input          = $params['input'];
		$data           = [
			'access_token'  => $input['access_token'],
			'refresh_token' => $input['refresh_token'],
		];
		$serialisedData = json_encode($data);

		return <<< HTML
<script type="application/javascript">
	window.opener.{$this->callbackMethod}($serialisedData);
</script>
HTML;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var ConnectorBox $connector */
		$connector = $this->getConnector();
		$connector->ping();

		// Store the absolute remote path in the property
		$directory        = $this->directory;
		$basename         = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$this->remotePath = $directory . '/' . $basename;

		// Get the remote file's pathname
		$remotePath = trim($directory, '/') . '/' . basename($localFilepath);

		// Single part upload
		$exception = null;

		// Try deleting the file first because Box doesn't allow replacing files
		Factory::getLog()->debug(sprintf("%s - Simple upload. Proactively deleting files with remote path $remotePath", __METHOD__));

		$connector->deleteFileByName($remotePath);

		try
		{
			// Try to upload the file (single part upload)
			Factory::getLog()->debug(sprintf("%s - Performing simple upload", __METHOD__));

			$connector->uploadSingleFile($remotePath, $localFilepath);
		}
		catch (Exception $e)
		{
			// Upload failed. Let's log the failure first.
			Factory::getLog()->debug(sprintf("%s - Simple upload failed, %s: %s", __METHOD__, 500, $e->getMessage()));

			// Increase the try counter
			$this->tryCount++;

			// If I exceeded my retry count I will throw am exception (hard failure)
			if ($this->tryCount > 2)
			{
				Factory::getLog()->debug(sprintf("%s - Maximum number of retries exceeded. The upload has failed.", __METHOD__));

				throw new RuntimeException('Uploading to Box failed.', 500, $e);
			}

			// I need to retry.
			Factory::getLog()->debug(__METHOD__ . " - Error detected, trying to force-refresh the tokens");

			$this->forceRefreshTokens();

			Factory::getLog()->debug(__METHOD__ . " - The upload will be retried");

			return false;
		}

		$this->tryCount = 0;

		return true;
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			// Ranges are not supported
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorBox $connector */
		$connector = $this->getConnector();
		$connector->ping();
		$connector->download($remotePath, $localFile);
	}

	public function delete($path)
	{
		/** @var ConnectorBox $connector */
		$connector = $this->getConnector();
		$connector->ping();
		$connector->deleteFileByName($path);
	}

	protected function getOAuth2HelperUrl()
	{
		return ConnectorBox::helperUrl;
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$access_token  = trim($config->get('engine.postproc.' . $this->settingsKey . '.access_token', ''));
		$refresh_token = trim($config->get('engine.postproc.' . $this->settingsKey . '.refresh_token', ''));

		$this->directory = $config->get('volatile.postproc.directory', null);

		if (empty($this->directory))
		{
			$this->directory = $config->get('engine.postproc.' . $this->settingsKey . '.directory', '');
		}

		// Sanity checks
		if (empty($refresh_token))
		{
			throw new BadConfiguration('You have not linked Akeeba Backup with your Box account');
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
			throw new BadConfiguration('You must enter your Download ID in the application configuration before using the “Upload to Box.com” feature.');
		}

		$connector = new ConnectorBox($access_token, $refresh_token, $dlid);

		// Validate the tokens
		Factory::getLog()->debug(sprintf("%s - Validating the Box tokens", __METHOD__));
		$pingResult = $connector->ping();

		// Save new configuration if there was a refresh
		if ($pingResult['needs_refresh'])
		{
			Factory::getLog()->debug(sprintf("%s - Box tokens were refreshed", __METHOD__));

			$config->set('engine.postproc.' . $this->settingsKey . '.access_token', $pingResult['access_token'], false);
			$config->set('engine.postproc.' . $this->settingsKey . '.refresh_token', $pingResult['refresh_token'], false);

			$profile_id = Platform::getInstance()->get_active_profile();

			Platform::getInstance()->save_configuration($profile_id);
		}

		return $connector;
	}

	/**
	 * Forcibly refresh the Box tokens
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function forceRefreshTokens()
	{
		/** @var ConnectorBox $connector */
		$connector  = $this->getConnector();
		$config     = Factory::getConfiguration();
		$pingResult = $connector->ping(true);

		Factory::getLog()->debug(sprintf("%s - Box tokens were forcibly refreshed", __METHOD__));

		$config->set('engine.postproc.' . $this->settingsKey . '.access_token', $pingResult['access_token'], false);
		$config->set('engine.postproc.' . $this->settingsKey . '.refresh_token', $pingResult['refresh_token'], false);

		$profile_id = Platform::getInstance()->get_active_profile();

		Platform::getInstance()->save_configuration($profile_id);
	}
}
