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
use Akeeba\Engine\Postproc\Connector\OneDrive as ConnectorOneDrive;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Onedrive extends Base
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
	 * Are we using chunk uploads?
	 *
	 * @var bool
	 */
	protected $isChunked = false;

	/**
	 * Chunk size (bytes)
	 *
	 * @var int
	 */
	protected $chunkSize = 10485760;

	/**
	 * The name of the OAuth2 callback method in the parent window (the configuration page)
	 *
	 * @var   string
	 */
	protected $callbackMethod = 'akeeba_onedrive_oauth_callback';

	/**
	 * The key in Akeeba Engine's settings registry for this post-processing method
	 *
	 * @var   string
	 */
	protected $settingsKey = 'onedrive';

	public function __construct()
	{
		$this->supportsDownloadToBrowser = true;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function oauthCallback(array $params)
	{
		$input = $params['input'];

		$data = (object) [
			'access_token'  => $input['access_token'],
			'refresh_token' => $input['refresh_token'],
		];

		$serialisedData = json_encode($data);

		return sprintf(
			'<script type="application/javascript">window.opener.%s(%s);</script>',
			$this->callbackMethod, $serialisedData
		);
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		// Do not remove, required to set up $this->directory used below.
		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();

		// Store the absolute remote path in the class property
		$directory        = $this->directory;
		$basename         = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$this->remotePath = $directory . '/' . $basename;

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		// Have I already made sure the remote directory exists?
		$haveCheckedRemoteDirectory = $config->get('volatile.engine.postproc.' . $this->settingsKey . '.check_directory', 0);

		if (!$haveCheckedRemoteDirectory)
		{
			Factory::getLog()->debug(
				sprintf(
					"%s -- Checking if OneDrive directory %s already exists or needs to be created.",
					__METHOD__, $directory
				)
			);

			try
			{
				$connector->ping();
				$connector->makeDirectory($directory);
			}
			catch (Exception $e)
			{
				throw new RuntimeException("Could not create directory $directory.", 500, $e);
			}

			$config->set('volatile.engine.postproc.' . $this->settingsKey . '.check_directory', 1);
		}

		// Get the remote file's pathname
		$remotePath = trim($directory, '/') . '/' . basename($localFilepath);

		// Check if the size of the file is compatible with chunked uploading
		clearstatcache();
		$totalSize = filesize($localFilepath) ?: 0;

		// Chunked uploads if the feature is enabled and the file is at least as big as the chunk size.
		if ($this->mustChunk($totalSize) || ($this->isChunked && !$this->mustSingeUpload($totalSize)))
		{
			return $this->multipartUpload($localFilepath, $remotePath, $totalSize);
		}

		// Single part upload
		return $this->simpleUpload($localFilepath, $remotePath);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			// Ranges are not supported
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();
		$connector->ping();

		// Download the file
		$connector->download($remotePath, $localFile);
	}

	public function downloadToBrowser($remotePath)
	{
		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();
		$connector->ping();

		return $connector->getSignedUrl($remotePath);
	}

	public function delete($path)
	{
		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();
		$connector->ping();

		$connector->delete($path);
	}

	/**
	 * Do I have to force a chunked upload?
	 *
	 * @param   int  $fileSize
	 *
	 * @return bool
	 */
	protected function mustChunk(int $fileSize): bool
	{
		return $fileSize > 104857600;
	}

	/**
	 * Do I have to force a single part upload?
	 *
	 * @param   int  $fileSize
	 *
	 * @return bool
	 */
	protected function mustSingeUpload(int $fileSize): bool
	{
		return ($fileSize <= $this->chunkSize) || ($fileSize <= 4194304);
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

			$profile_id = Platform::getInstance()->get_active_profile();
			Platform::getInstance()->save_configuration($profile_id);
		}

		return $connector;
	}

	/**
	 * Forcibly refresh the OneDrive tokens
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function forceRefreshTokens()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		/** @var ConnectorOneDrive $connector */
		$connector  = $this->getConnector();
		$pingResult = $connector->ping(true);

		Factory::getLog()->debug(__METHOD__ . " - OneDrive tokens were forcibly refreshed");
		$config->set('engine.postproc.' . $this->settingsKey . '.access_token', $pingResult['access_token'], false);
		$config->set('engine.postproc.' . $this->settingsKey . '.refresh_token', $pingResult['refresh_token'], false);

		$profile_id = Platform::getInstance()->get_active_profile();
		Platform::getInstance()->save_configuration($profile_id);
	}

	protected function getOAuth2HelperUrl()
	{
		return ConnectorOneDrive::helperUrl;
	}

	/**
	 * Performs a multipart (chunked) upload.
	 *
	 * @param   string  $localFilepath  The path to the local file we'll be uploading.
	 * @param   string  $remotePath     The path to the file in remote storage
	 * @param   int     $totalSize      The total size of the file, in bytes.
	 *
	 * @return  bool  True if the upload is complete, false if more work is necessary
	 * @throws  Exception  If an upload error occurs
	 */
	private function multipartUpload($localFilepath, $remotePath, $totalSize)
	{
		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		// Are we already processing a multipart upload?
		Factory::getLog()->debug(
			sprintf(
				"%s - Using chunked upload, part size %d",
				__METHOD__, $this->chunkSize
			)
		);

		$offset    = $config->get('volatile.engine.postproc.' . $this->settingsKey . '.offset', 0);
		$upload_id = $config->get('volatile.engine.postproc.' . $this->settingsKey . '.upload_id', null);

		if (empty($upload_id))
		{
			Factory::getLog()->debug(
				sprintf(
					"%s - Trying to remove existing file by the same name (%s)",
					__METHOD__, $remotePath
				)
			);

			// Try deleting the file first because OneDrive doesn't allow replacing files when using multipart uploads
			$connector->delete($remotePath, false);

			Factory::getLog()->debug(
				sprintf(
					"%s - Creating new upload session",
					__METHOD__
				)
			);

			try
			{
				$upload_id = $connector->createUploadSession($remotePath);
			}
			catch (Exception $e)
			{
				Factory::getLog()->debug(
					sprintf(
						"%s - Failed to create a new upload session; will try to refresh the tokens first",
						__METHOD__
					)
				);

				$upload_id = null;
			}

			if (is_null($upload_id))
			{
				try
				{
					$this->forceRefreshTokens();

					$upload_id = $connector->createUploadSession($remotePath);
				}
				catch (Exception $e)
				{
					throw new RuntimeException(
						sprintf(
							"The upload session for remote file %s cannot be created",
							$remotePath
						), 500, $e
					);
				}
			}

			Factory::getLog()->debug(
				sprintf(
					"%s - New upload session %s",
					__METHOD__, $upload_id
				)
			);

			$config->set('volatile.engine.postproc.' . $this->settingsKey . '.upload_id', $upload_id);
		}

		try
		{
			if (empty($offset))
			{
				$offset = 0;
			}

			Factory::getLog()->debug(
				sprintf(
					"%s - Uploading chunked part",
					__METHOD__
				)
			);

			$result = $connector->uploadPart($upload_id, $localFilepath, $offset, $this->chunkSize);

			Factory::getLog()->debug(
				sprintf(
					"%s - Got uploadPart result %s",
					__METHOD__, print_r($result, true)
				)
			);
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(
				sprintf(
					"%s - Got uploadPart Exception %s: %s",
					__METHOD__, $e->getCode(), $e->getMessage()
				)
			);

			// Let's retry
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				throw new RuntimeException(
					sprintf(
						"%s - Maximum number of retries exceeded. The upload has failed.",
						__METHOD__
					), 500, $e
				);
			}

			Factory::getLog()->debug(
				sprintf(
					"%s - Error detected, trying to force-refresh the tokens",
					__METHOD__
				)
			);

			$this->forceRefreshTokens();

			Factory::getLog()->debug(
				sprintf(
					"%s - Retrying chunk upload",
					__METHOD__
				)
			);

			return false;
		}

		// Are we done uploading?
		$nextOffset = $offset + $this->chunkSize - 1;

		if (isset($result['name']) || ($nextOffset > $totalSize))
		{
			Factory::getLog()->debug(
				sprintf(
					"%s - Chunked upload is now complete",
					__METHOD__
				)
			);

			$config->set('volatile.engine.postproc.' . $this->settingsKey . '.offset', null);
			$config->set('volatile.engine.postproc.' . $this->settingsKey . '.upload_id', null);

			$this->tryCount = 0;

			return true;
		}

		// Otherwise, continue uploading
		$config->set('volatile.engine.postproc.' . $this->settingsKey . '.offset', $offset + $this->chunkSize);

		return false;
	}

	/**
	 * Performs a single part upload
	 *
	 * @param   string  $localFilepath
	 * @param   string  $remotePath
	 *
	 * @return  bool  True if the upload is complete, false if more work is necessary
	 * @throws  Exception  If an upload error occurs
	 */
	private function simpleUpload($localFilepath, $remotePath)
	{
		/** @var ConnectorOneDrive $connector */
		$connector = $this->getConnector();

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		try
		{
			Factory::getLog()->debug(__METHOD__ . " - Simple upload. Proactively deleting files with remote path " . $remotePath);

			// Try deleting the file first because OneDrive doesn't allow replacing files when using multipart uploads
			$connector->delete($remotePath, false);

			Factory::getLog()->debug(__METHOD__ . " - Performing simple upload");

			$connector->simpleUpload($remotePath, $localFilepath);
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(__METHOD__ . " - Simple upload failed, " . $e->getCode() . ": " . $e->getMessage());

			// Let's retry
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				throw new RuntimeException(__METHOD__ . " - Maximum number of retries exceeded. The upload has failed.", 500, $e);
			}

			Factory::getLog()->debug(__METHOD__ . " - Error detected, trying to force-refresh the tokens");

			$this->forceRefreshTokens();

			Factory::getLog()->debug(__METHOD__ . " - Retrying upload");

			return false;
		}

		// Upload complete. Reset the retry counter.
		$this->tryCount = 0;

		return true;
	}
}
