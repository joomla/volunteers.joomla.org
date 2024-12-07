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

use Akeeba\Engine\Base\Exceptions\ErrorException;
use Akeeba\Engine\Configuration;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Postproc\Connector\Dropbox2 as ConnectorDropboxV2;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Dropbox2 extends Base
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
	 * Are we using chunk uploads?
	 *
	 * @var bool
	 */
	private $chunked = false;

	/**
	 * Chunk size (MB)
	 *
	 * @var int
	 */
	private $chunk_size = 10;

	public function __construct()
	{
		$this->supportsDownloadToBrowser = true;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function oauthCallback(array $params)
	{
		$input = $params['input'];
		$data  = (object) [
			'access_token'  => $input['access_token'],
			'refresh_token' => $input['refresh_token'],
		];

		$serialisedData = json_encode($data);

		return <<< HTML
<script type="application/javascript">
	window.opener.akeeba_dropbox2_oauth_callback($serialisedData);
</script>
HTML;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();
		$config    = Factory::getConfiguration();

		// Store the absolute remote path in the class property
		$directory        = $this->directory;
		$basename         = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$this->remotePath = $directory . '/' . $basename;

		// Have I already made sure the remote directory exists?
		$haveCheckedRemoteDirectory = $config->get('volatile.engine.postproc.dropbox2.check_directory', 0);

		if (!$haveCheckedRemoteDirectory)
		{
			try
			{
				$connector->ping();
				$connector->makeDirectory($directory);
				$config->set('volatile.engine.postproc.dropbox2.check_directory', 1);
			}
			catch (Exception $e)
			{
				throw new RuntimeException(sprintf("Could not create Dropbox directory %s.", $directory), 500, $e);
			}
		}

		// Get the remote file's pathname
		$remotePath = trim($directory, '/') . '/' . basename($localFilepath);

		// Check if the size of the file is compatible with chunked uploading
		clearstatcache();
		$totalSize   = filesize($localFilepath);
		$isBigEnough = $this->chunked ? ($totalSize > $this->chunk_size) : false;

		// Chunked uploads if the feature is enabled and the file is at least as big as the chunk size.
		if ($this->chunked && $isBigEnough)
		{
			return $this->processPartChunked($config, $localFilepath, $remotePath, $totalSize);
		}

		// Single part upload
		return $this->processPartSingleUpload($localFilepath, $remotePath);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();

		if (!is_null($fromOffset))
		{
			throw new RangeDownloadNotSupported();
		}

		// Download the file
		$connector->ping();
		$connector->download($remotePath, $localFile);

		return true;
	}

	public function downloadToBrowser($remotePath)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();
		$connector->ping();

		return $connector->getAuthenticatedUrl($remotePath);
	}

	public function delete($path)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();
		$connector->ping();

		$connector->delete($path);
	}

	protected function getOAuth2HelperUrl()
	{
		return ConnectorDropboxV2::helperUrl;
	}

	/**
	 * Returns the Dropbox configuration settings
	 *
	 * @return  array
	 */
	protected function getSettings()
	{
		// Retrieve engine configuration data
		$config           = Factory::getConfiguration();
		$accessToken      = trim($config->get('engine.postproc.dropbox2.access_token', ''));
		$refreshToken     = trim($config->get('engine.postproc.dropbox2.refresh_token', ''));
		$this->chunked    = $config->get('engine.postproc.dropbox2.chunk_upload', true);
		$this->chunk_size = $config->get('engine.postproc.dropbox2.chunk_upload_size', 10) * 1024 * 1024;
		$defaultDirectory = $config->get('engine.postproc.dropbox2.directory', '');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Sanity checks
		$dlid = Platform::getInstance()->get_platform_configuration_option('update_dlid', '');

		if (empty($dlid))
		{
			throw new BadConfiguration('You must enter your Download ID in the application configuration before using the “Upload to Dropbox” feature.');
		}

		if (empty($accessToken) && empty($refreshToken))
		{
			throw new BadConfiguration('You have not linked Akeeba Backup with your Dropbox account');
		}

		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		/**
		 * Remove the trailing and leading slashes from the directory. Why? The directory must never have a trailing
		 * slash. The directory must have a leading slash UNLESS it's the root, in which case it needs to be normalized
		 * to an empty string. So we first remove leading & trailing slashes, then check if it's empty (root) or not.
		 *
		 * Then remove any leftover leading/trailing slash. They will produce an invalid path in Dropbox. However, if
		 * the directory is not empty we must prefix it with a leading slash.
		 */
		$this->directory = trim($this->directory, '/');
		$this->directory = trim($this->directory);
		$this->directory = empty($this->directory) ? '' : ('/' . $this->directory);
		$this->directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($this->directory), '/');
		$this->directory = Factory::getFilesystemTools()->replace_archive_name_variables($this->directory);
		$config->set('volatile.postproc.directory', $this->directory);

		return [
			'token'         => $accessToken,
			'refresh_token' => $refreshToken,
			'dlid'          => $dlid,
		];
	}

	protected function makeConnector()
	{
		// Do we have a cached root namespace ID?
		$configuration = Factory::getConfiguration();
		$namespaceId   = $configuration->get('volatile.engine.postproc.dropbox2.namespaceId', null);

		// Get and cache the root namespace ID.
		if (is_null($namespaceId))
		{
			$namespaceId = $this->getDropboxForBusinessRootNamespaceId();
			$configuration->set('volatile.engine.postproc.dropbox2.namespaceId', $namespaceId);
		}

		$config    = $this->getSettings();
		$connector = new ConnectorDropboxV2($config['token'], $config['refresh_token'], $config['dlid']);

		$connector->setNamespaceId($namespaceId);

		return $connector;
	}

	/**
	 * Forcibly refresh the Dropbox tokens
	 *
	 * @param   ConnectorDropboxV2|null  $connector  The connector to use
	 *
	 * @return  void
	 *
	 * @throws Exception
	 */
	protected function forceRefreshTokens($connector = null)
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		/** @var ConnectorDropboxV2 $connector */
		if (empty($connector))
		{
			$connector = $this->getConnector();
		}

		$pingResult = $connector->ping(true);

		Factory::getLog()->debug(__METHOD__ . " - Dropbox tokens were forcibly refreshed");
		$config->set('engine.postproc.dropbox2.access_token', $pingResult['access_token'], false);
		$config->set('engine.postproc.dropbox2.refresh_token', $pingResult['refresh_token'], false);

		$profile_id = Platform::getInstance()->get_active_profile();
		Platform::getInstance()->save_configuration($profile_id);
	}

	/**
	 * Returns the root namespace for the Dropbox for Business team space
	 *
	 * @see     https://www.dropbox.com/developers/reference/namespace-guide
	 *
	 * @param   bool  $refreshTokensOnFailure  Should I try to forcibly update the token on connection failure?
	 *
	 * @return  string
	 * @throws  Exception
	 */
	private function getDropboxForBusinessRootNamespaceId($refreshTokensOnFailure = true)
	{
		// Do I need to use a folder under a team space?
		$configuration = Factory::getConfiguration();
		$useTeam       = $configuration->get('engine.postproc.dropbox2.team', 0) == 1;

		// Team space is not in use. Default to the user's home (same as the legacy behavior)
		if (!$useTeam)
		{
			return '';
		}

		// Try to get the current account information
		try
		{
			$config         = $this->getSettings();
			$connector      = new ConnectorDropboxV2($config['token'], $config['refresh_token'], $config['dlid']);
			$currentAccount = $connector->getCurrentAccount();
		}
		catch (Exception $e)
		{
			if ($refreshTokensOnFailure)
			{
				$this->forceRefreshTokens($connector);

				return $this->getDropboxForBusinessRootNamespaceId(false);
			}

			throw new ErrorException("Cannot connect to Dropbox for Business", 0, $e);
		}

		if (
			!is_array($currentAccount)
			|| !array_key_exists('root_info', $currentAccount)
			|| !is_array($currentAccount['root_info'])
			|| !array_key_exists('root_namespace_id', $currentAccount['root_info'])
		)
		{
			throw new ErrorException("Dropbox for Business did not return any user account information");
		}

		return $currentAccount['root_info']['root_namespace_id'];
	}

	/**
	 * Handles the multipart upload of a file to Dropbox
	 *
	 * @param   Configuration  $config
	 * @param   string         $absolute_filename
	 * @param   string         $remotePath
	 * @param   int            $totalSize
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 */
	private function processPartChunked(Configuration $config, $absolute_filename, $remotePath, $totalSize)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();

		// Are we already processing a multipart upload?
		Factory::getLog()->debug(sprintf(
			"%s - Using chunked upload, part size {$this->chunk_size}", __METHOD__
		));

		$offset    = $config->get('volatile.engine.postproc.dropbox2.offset', 0);
		$upload_id = $config->get('volatile.engine.postproc.dropbox2.upload_id', null);

		if (empty($upload_id))
		{
			Factory::getLog()->debug(sprintf(
				"%s - Creating new upload session", __METHOD__
			));

			try
			{
				$upload_id = $connector->createUploadSession();
			}
			catch (Exception $e)
			{
				// Fail immediately if there is no refresh token
				$config = $this->getSettings();
				if (empty($config['refresh_token']))
				{
					throw new RuntimeException("Cannot create upload session", 500, $e);
				}

				Factory::getLog()->debug(sprintf(
					"%s - Failed to create a new upload session; will try to refresh the tokens first",
					__METHOD__
				));

				$upload_id = null;
			}

			if (is_null($upload_id))
			{
				try
				{
					$this->forceRefreshTokens();

					$upload_id = $connector->createUploadSession();
				}
				catch (Exception $e)
				{
					throw new RuntimeException("Cannot create upload session", 500, $e);
				}
			}

			Factory::getLog()->debug(sprintf(
				"%s - New upload session $upload_id", __METHOD__
			));

			$config->set('volatile.engine.postproc.dropbox2.upload_id', $upload_id);
		}

		$exception = null;

		try
		{
			if (empty($offset))
			{
				$offset = 0;
			}

			Factory::getLog()->debug(sprintf("%s - Uploading chunked part", __METHOD__));

			$connector->uploadPart($upload_id, $absolute_filename, $offset, $this->chunk_size);

			$result = true;
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf(
					"%s - Got uploadPart Exception %s: %s",
					__METHOD__, 500, $e->getMessage())
			);

			$exception = $e;

			$result = false;
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

			// Retry to refresh the Access Token if a Refresh Token is provided.
			$config = $this->getSettings();

			if (!empty($config['refresh_token']))
			{
				Factory::getLog()->debug(sprintf(
					"%s - Error detected, trying to force-refresh the tokens",
					__METHOD__
				));

				$this->forceRefreshTokens();
			}

			Factory::getLog()->debug(sprintf("%s - Retrying chunk upload", __METHOD__));

			return false;
		}

		// Are we done uploading?
		$nextOffset = $offset + $this->chunk_size - 1;

		if (isset($result['name']) || ($nextOffset > $totalSize))
		{
			Factory::getLog()->debug(sprintf(
				"%s - Finializing chunked upload, saving uploaded file as %s",
				__METHOD__, $remotePath
			));

			try
			{
				$connector->finishUploadSession($upload_id, $remotePath, $totalSize);
			}
			catch (Exception $e)
			{
				throw new RuntimeException('Chunk upload finalization failed', 500, $e);
			}

			Factory::getLog()->debug(sprintf(
				"%s - Chunked upload is now complete",
				__METHOD__
			));

			$config->set('volatile.engine.postproc.dropbox2.offset', null);
			$config->set('volatile.engine.postproc.dropbox2.upload_id', null);

			return true;
		}

		// Otherwise, continue uploading
		$config->set('volatile.engine.postproc.dropbox2.offset', $offset + $this->chunk_size);

		return false;
	}

	/**
	 * Handles the single part upload of a file to Dropbox
	 *
	 * @param   string  $absolute_filename
	 * @param   string  $remotePath
	 *
	 * @return  bool
	 *
	 * @throws  Exception
	 */
	private function processPartSingleUpload($absolute_filename, $remotePath)
	{
		/** @var ConnectorDropboxV2 $connector */
		$connector = $this->getConnector();
		$exception = null;

		try
		{
			Factory::getLog()->debug(sprintf(
				"%s - Performing simplified upload of %s to %s",
				__METHOD__, $absolute_filename, $remotePath
			));

			$result = $connector->upload($remotePath, $absolute_filename);
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf(
				"%s - Simplified upload failed, %s: %s",
				__METHOD__, $e->getCode(), $e->getMessage()
			));

			$exception = $e;
			$result    = false;
		}

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

			// Retry to refresh the Access Token if a Refresh Token is provided.
			$config = $this->getSettings();

			if (!empty($config['refresh_token']))
			{
				Factory::getLog()->debug(sprintf(
					"%s - Error detected, trying to force-refresh the tokens",
					__METHOD__
				));

				$this->forceRefreshTokens();
			}

			Factory::getLog()->debug(sprintf("%s - Retrying upload", __METHOD__));

			return false;
		}

		// Upload complete. Reset the retry counter.
		$this->tryCount = 0;

		return true;
	}

}
