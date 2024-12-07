<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\GoogleDrive as ConnectorGoogleDrive;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Awf\Text\Text;
use Exception;
use Joomla\CMS\Language\Text as JText;
use RuntimeException;

class Googledrive extends Base
{
	/**
	 * The retry count of this file (allow up to 2 retries after the first upload failure)
	 *
	 * @var int
	 */
	private $tryCount = 0;

	/**
	 * The currently configured directory
	 *
	 * @var string
	 */
	private $directory;

	/**
	 * Chunk size (MB)
	 *
	 * @var int
	 */
	private $chunkSize = 10;

	/**
	 * Overrides of any configuration variables when creating a connector object. Used when trying to get a list of
	 * drives through AJAX.
	 *
	 * @var   array
	 */
	private $configOverrides = [];

	public function __construct()
	{
		$this->supportsDownloadToBrowser     = false;
		$this->supportsDelete                = true;
		$this->supportsDownloadToFile        = true;
		$this->allowedCustomAPICallMethods[] = 'getDrives';
	}

	public function oauthCallback(array $params)
	{
		$input = $params['input'];

		$data = (object) [
			'access_token'  => $input['access_token'],
			'refresh_token' => $input['refresh_token'],
		];

		$serialisedData = json_encode($data);

		return <<< HTML
<script type="application/javascript">
	window.opener.akeeba_googledrive_oauth_callback($serialisedData);
</script>
HTML;
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
		$baseItem = 'Google Drive (personal)';

		if (class_exists('\Joomla\CMS\Language\Text'))
		{
			$baseItem = JText::_('COM_AKEEBA_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL');

			if ($baseItem === 'COM_AKEEBA_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL')
			{
				$baseItem = JText::_('COM_AKEEBABACKUP_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL');
			}
		}

		if (class_exists('\Awf\Text\Text'))
		{
			$baseItem = Text::_('COM_AKEEBA_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL');

			if ($baseItem === 'COM_AKEEBA_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL')
			{
				$baseItem = Text::_('COM_AKEEBABACKUP_CONFIG_GOOGLEDRIVE_TEAMDRIVE_OPT_PERSONAL');
			}
		}

		$items = [
			'' => $baseItem,
		];

		// Try to get a list of Team Drives
		try
		{
			$this->configOverrides = $params;
			/** @var ConnectorGoogleDrive $connector */
			$connector = $this->getConnector(true);
			$connector->ping();

			$items = array_merge($items, $connector->getTeamDrives());
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

	/**
	 * This function takes care of post-processing a backup archive's part, or the
	 * whole backup archive if it's not a split archive type. If the process fails
	 * it should return false. If it succeeds and the entirety of the file has been
	 * processed, it should return true. If only a part of the file has been uploaded,
	 * it must return 1.
	 *
	 * @param   string  $localFilepath   Absolute path to the part we'll have to process
	 * @param   string  $remoteBaseName  Base name of the uploaded file, skip to use $absolute_filename's
	 *
	 * @return  boolean|integer  False on failure, true on success, 1 if more work is required
	 *
	 * @throws Exception
	 */
	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var ConnectorGoogleDrive $connector */
		$connector = $this->getConnector(true);

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		// Store the absolute remote path in the class property
		$directory        = $this->directory;
		$basename         = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$this->remotePath = trim($directory, '/') . '/' . $basename;

		// Have I already made sure the remote directory exists?
		$folderId    = $config->get('volatile.engine.postproc.googledrive.check_directory', 0);
		$teamDriveID = $config->get('engine.postproc.googledrive.team_drive', '');

		if (!$folderId)
		{
			try
			{
				Factory::getLog()->debug(sprintf(
					"%s - Preparing to upload to Google Drive, file path = %s.",
					__METHOD__, $this->remotePath
				));

				$connector->ping();

				[$fileName, $folderId] = $connector->preprocessUploadPath($this->remotePath, $teamDriveID);

				Factory::getLog()->debug(sprintf(
					"%s - Google Drive folder ID = %s",
					__METHOD__, $folderId
				));
			}
			catch (Exception $e)
			{
				throw new RuntimeException(sprintf(
					"Could not create Google Drive directory %s. ", $directory
				), 500, $e);
			}

			$config->set('volatile.engine.postproc.googledrive.check_directory', $folderId);
		}

		// Get the remote file's pathname
		$remotePath = $this->remotePath;

		// Check if the size of the file is compatible with chunked uploading
		clearstatcache();
		$totalSize = filesize($localFilepath);

		/**
		 * Google Drive is broken.
		 *
		 * When you use Simple Upload it will upload your files in two(!!!) places at the same time: the folder you tell
		 * it and the Drive's root. Why? Nobody knows. It's not what the documentation purports the API should be doing.
		 *
		 * The kinda daft way around this is using chunked upload even for tiny files, less than the part size. Many
		 * more requests to the API server yet it works. That's Google for you, man...
		 */
		Factory::getLog()->debug(sprintf(
			"%s - Using chunked upload, part size %d",
			__METHOD__, $this->chunkSize
		));

		$offset    = $config->get('volatile.engine.postproc.googledrive.offset', 0);
		$upload_id = $config->get('volatile.engine.postproc.googledrive.upload_id', null);

		if (empty($upload_id))
		{
			// Convert path to folder ID and file ID, creating missing folders and deleting existing files in the process
			Factory::getLog()->debug(sprintf(
				"%s - Trying to create possibly missing directories and remove existing file by the same name (%s)",
				__METHOD__, $remotePath
			));

			$connector->ping();

			[$fileName, $folderId] = $connector->preprocessUploadPath($remotePath, $teamDriveID);

			Factory::getLog()->debug(sprintf(
				"%s - Creating new upload session",
				__METHOD__
			));

			try
			{
				$upload_id = $connector->createUploadSession($folderId, $localFilepath, $fileName);
			}
			catch (Exception $e)
			{
				throw new RuntimeException(sprintf(
					"The upload session for remote file %s cannot be created.", $remotePath
				), 500, $e);
			}

			Factory::getLog()->debug(sprintf(
				"%s - New upload session %s",
				__METHOD__, $upload_id
			));
			$config->set('volatile.engine.postproc.googledrive.upload_id', $upload_id);
		}

		$exception = null;

		try
		{
			if (empty($offset))
			{
				$offset = 0;
			}

			Factory::getLog()->debug(sprintf(
				"%s - Uploading chunked part (offset:$offset // chunk size: %d)",
				__METHOD__, $this->chunkSize
			));

			$result = $connector->uploadPart($upload_id, $localFilepath, $offset, $this->chunkSize);

			Factory::getLog()->debug(sprintf(
				"%s - Got uploadPart result %s",
				__METHOD__, print_r($result, true)
			));
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf(
				"%s - Got uploadPart Exception %s: %s",
				__METHOD__, $e->getCode(), $e->getMessage()
			));

			$exception = $e;
			$result    = false;
		}

		// Did we fail uploading?
		if ($result === false)
		{
			// Let's retry
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				throw new RuntimeException(sprintf(
					"%s - Maximum number of retries exceeded. The upload has failed.",
					__METHOD__
				), 500, $exception);
			}

			Factory::getLog()->debug(sprintf(
				"%s - Error detected, trying to force-refresh the tokens",
				__METHOD__
			));

			$this->forceRefreshTokens();

			Factory::getLog()->debug(sprintf(
				"%s - Retrying chunk upload",
				__METHOD__
			));

			return false;
		}

		// Are we done uploading?
		$nextOffset = $offset + $this->chunkSize - 1;

		if (isset($result['name']) || ($nextOffset > $totalSize))
		{
			Factory::getLog()->debug(sprintf(
				"%s - Chunked upload is now complete",
				__METHOD__
			));

			$config->set('volatile.engine.postproc.googledrive.offset', null);
			$config->set('volatile.engine.postproc.googledrive.upload_id', null);

			$this->tryCount = 0;

			return true;
		}

		// Otherwise, continue uploading
		$config->set('volatile.engine.postproc.googledrive.offset', $offset + $this->chunkSize);

		return false;
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorGoogleDrive $connector */
		$connector = $this->getConnector(true);
		$connector->ping();

		// Download the file
		$engineConfig = Factory::getConfiguration();
		$teamDriveID  = $engineConfig->get('engine.postproc.googledrive.team_drive', '');
		$fileId       = $connector->getIdForFile($remotePath, false, $teamDriveID);

		$connector->download($fileId, $localFile);
	}

	public function delete($path)
	{
		/** @var ConnectorGoogleDrive $connector */
		$connector = $this->getConnector(true);
		$connector->ping();

		$engineConfig = Factory::getConfiguration();
		$teamDriveID  = $engineConfig->get('engine.postproc.googledrive.team_drive', '');
		$fileId       = $connector->getIdForFile($path, false, $teamDriveID);

		$connector->delete($fileId, true);
	}

	protected function getOAuth2HelperUrl()
	{
		return ConnectorGoogleDrive::helperUrl;
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		if (!empty($this->configOverrides))
		{
			$config->mergeArray($this->configOverrides);
		}

		$accessToken      = trim($config->get('engine.postproc.googledrive.access_token', ''));
		$refreshToken     = trim($config->get('engine.postproc.googledrive.refresh_token', ''));
		$this->chunkSize  = $config->get('engine.postproc.googledrive.chunk_upload_size', 10) * 1024 * 1024;
		$defaultDirectory = $config->get('engine.postproc.googledrive.directory', '');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Sanity checks
		if (empty($refreshToken))
		{
			throw new BadConfiguration('You have not linked Akeeba Backup with your Google Drive account');
		}

		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		$dlid = Platform::getInstance()->get_platform_configuration_option('update_dlid', '');

		if (empty($dlid))
		{
			throw new BadConfiguration('You must enter your Download ID in the application configuration before using the “Upload to Google Drive” feature.');
		}

		// Fix the directory name, if required
		$this->directory = empty($this->directory) ? '' : $this->directory;
		$this->directory = trim($this->directory);
		$this->directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($this->directory), '/');
		$this->directory = Factory::getFilesystemTools()->replace_archive_name_variables($this->directory);
		$config->set('volatile.postproc.directory', $this->directory);

		$connector = new ConnectorGoogleDrive($accessToken, $refreshToken, $dlid);

		// Validate the tokens
		Factory::getLog()->debug(sprintf(
			"%s - Validating the Google Drive tokens",
			__METHOD__
		));

		$pingResult = $connector->ping();

		// Save new configuration if there was a refresh
		if ($pingResult['needs_refresh'])
		{
			Factory::getLog()->debug(sprintf(
				"%s - Google Drive tokens were refreshed",
				__METHOD__
			));

			$config->set('engine.postproc.googledrive.access_token', $pingResult['access_token'], false);

			$profile_id = Platform::getInstance()->get_active_profile();
			Platform::getInstance()->save_configuration($profile_id);
		}

		return $connector;
	}

	/**
	 * Forcibly refresh the Google Drive tokens
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function forceRefreshTokens()
	{
		/** @var ConnectorGoogleDrive $connector */
		$connector = $this->getConnector(true);

		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$pingResult = $connector->ping(true);

		Factory::getLog()->debug(sprintf(
			"%s - Google Drive tokens were forcibly refreshed",
			__METHOD__
		));
		$config->set('engine.postproc.googledrive.access_token', $pingResult['access_token'], false);

		$profile_id = Platform::getInstance()->get_active_profile();

		Platform::getInstance()->save_configuration($profile_id);
	}
}
