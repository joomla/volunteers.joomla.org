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
use Akeeba\Engine\Postproc\Connector\Backblaze as ConnectorBackblaze;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\Base as BackblazeBaseException;
use Akeeba\Engine\Postproc\Connector\Backblaze\Exception\UnexpectedHTTPStatus;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Exception;
use OutOfBoundsException;
use RuntimeException;

/**
 * Upload to Backblaze post-processing engine for Akeeba Engine
 */
class Backblaze extends Base
{
	/**
	 * The upload ID of the multipart upload in progress
	 *
	 * @var   null|string
	 */
	protected $fileId = null;

	/**
	 * The Upload URL structure returned by Backblaze, required in multipart uplaods
	 *
	 * @var   ConnectorBackblaze\UploadURL
	 */
	protected $uploadUrl = null;

	/**
	 * The part number for the multipart upload in progress
	 *
	 * @var null|int
	 */
	protected $partNumber = null;

	/**
	 * The SHA-1 checksums of the uploaded chunks, used to finalise the multipart upload
	 *
	 * @var  array
	 */
	protected $sha1Parts = [];

	/**
	 * Used in log messages.
	 *
	 * @var  string
	 */
	protected $engineLogName = 'BackBlaze B2';

	/**
	 * The prefix to use for volatile key storage
	 *
	 * @var  string
	 */
	protected $volatileKeyPrefix = 'volatile.postproc.backblaze.';

	/**
	 * The ID of the bucket specified in the engine configuration
	 *
	 * @var  string
	 */
	protected $bucketId = '';

	/**
	 * Initialise the class, setting its capabilities
	 */
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToBrowser = true;
		$this->supportsDownloadToFile    = true;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		// Retrieve engine configuration data
		$akeebaConfig = Factory::getConfiguration();

		// Load multipart information from temporary storage
		$this->fileId    = $akeebaConfig->get($this->volatileKeyPrefix . 'fileId', null);
		$this->uploadUrl = new ConnectorBackblaze\UploadURL($akeebaConfig->get($this->volatileKeyPrefix . 'uploadUrl', []));

		// Get the configuration parameters
		$engineConfig     = $this->getEngineConfiguration();
		$disableMultipart = $engineConfig['disableMultipart'];

		// The directory is a special case. First try getting a cached directory
		$directory        = $akeebaConfig->get('volatile.postproc.directory', null);
		$processDirectory = false;

		// If there is no cached directory, fetch it from the engine configuration
		if (is_null($directory))
		{
			$directory        = $engineConfig['directory'];
			$processDirectory = true;
		}

		// The very first time we deal with the directory we need to process it.
		if ($processDirectory)
		{
			$directory = empty($directory) ? '' : $directory;
			$directory = str_replace('\\', '/', $directory);
			$directory = rtrim($directory, '/');
			$directory = trim($directory);
			$directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($directory), '/');
			$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);

			// Store the parsed directory in temporary storage
			$akeebaConfig->set('volatile.postproc.directory', $directory);
		}

		/**
		 * Get the file size and disable multipart uploads for files smaller than 5Mb or the configured chunk size,
		 * whichever is bigger.
		 */
		$fileSize = @filesize($localFilepath);
		$partSize = max($this->getPartSizeForFile($localFilepath), 5242880);

		if ($fileSize <= $partSize)
		{
			$disableMultipart = true;
		}

		// Calculate relative remote filename
		$remoteKey = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;

		if (!empty($directory) && ($directory != '/'))
		{
			$remoteKey = $directory . '/' . $remoteKey;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $remoteKey;

		/** @var ConnectorBackblaze $connector */
		$connector = $this->getConnector();
		$bucketId  = $this->getBucketId();

		// Are we already processing a multipart upload or asked to perform a multipart upload?
		if (!empty($this->fileId) || !$disableMultipart)
		{
			$this->partNumber = $akeebaConfig->get($this->volatileKeyPrefix . 'partNumber', null);
			$this->sha1Parts  = $akeebaConfig->get($this->volatileKeyPrefix . 'sha1Parts', '[]');
			$this->sha1Parts  = json_decode($this->sha1Parts, true);
			$this->sha1Parts  = empty($this->sha1Parts) ? [] : $this->sha1Parts;

			return $this->multipartUpload($bucketId, $remoteKey, $localFilepath, $connector);
		}

		return $this->simpleUpload($bucketId, $remoteKey, $localFilepath, $connector);
	}

	public function delete($path)
	{
		/** @var ConnectorBackblaze $connector */
		$connector = $this->getConnector();
		$bucketId  = $this->getBucketId();
		$connector->deleteByFileName($bucketId, $path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		/** @var ConnectorBackblaze $connector */
		$connector    = $this->getConnector();
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);
		$headers      = [];

		if (!is_null($fromOffset) && $length)
		{
			$toOffset  = $fromOffset + $length - 1;
			$headers[] = 'Range: bytes=' . $fromOffset . '-' . $toOffset;
		}

		$connector->downloadFile($bucket, $remotePath, $localFile, $headers);
	}

	public function downloadToBrowser($remotePath)
	{
		/** @var ConnectorBackblaze $connector */
		$connector    = $this->getConnector();
		$engineConfig = $this->getEngineConfiguration();
		$bucket       = $engineConfig['bucket'];
		$bucket       = str_replace('/', '', $bucket);

		return $connector->getSignedUrl($bucket, $remotePath, 30);
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = $this->getEngineConfiguration();

		// Get the configuration parameters
		$accountId      = $config['accountId'];
		$applicationKey = $config['applicationKey'];
		$bucket         = $config['bucket'];

		// Remove any slashes from the bucket
		$bucket = str_replace('/', '', $bucket);

		// Sanity checks
		if (empty($accountId))
		{
			throw new BadConfiguration('You have not set up your ' . $this->engineLogName . ' Account ID');
		}

		if (empty($applicationKey))
		{
			throw new BadConfiguration('You have not set up your ' . $this->engineLogName . ' Application Key');
		}

		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		if (empty($bucket))
		{
			throw new BadConfiguration('You have not set up your ' . $this->engineLogName . ' Bucket');
		}

		// Create the API connector instance
		return new ConnectorBackblaze($accountId, $applicationKey);
	}

	/**
	 * Start a multipart upload
	 *
	 * @param   string              $bucketId    The bucket ID to upload to
	 * @param   string              $remoteKey   The remote filename
	 * @param   string              $sourceFile  The full path to the local source file
	 * @param   ConnectorBackblaze  $connector   The S3 client object instance
	 *
	 * @return  bool  True when we're done uploading, false if we have more parts
	 *
	 * @throws  Exception  When an error occurs
	 */
	private function multipartUpload($bucketId, $remoteKey, $sourceFile, ConnectorBackblaze $connector)
	{
		if (empty($this->fileId))
		{
			Factory::getLog()->debug(sprintf(
				"%s -- Beginning multipart upload of %s", $this->engineLogName, $sourceFile
			));

			// Initialise the multipart upload if necessary
			try
			{
				$fileInfo     = $connector->startUpload($bucketId, $remoteKey);
				$this->fileId = $fileInfo->fileId;

				Factory::getLog()->debug(sprintf(
					"%s -- Got fileID %s", $this->engineLogName, $this->fileId
				));

				$this->uploadUrl  = $connector->getPartUploadUrl($this->fileId);
				$this->partNumber = 1;
				$this->sha1Parts  = [];

				Factory::getLog()->debug(sprintf(
					"%s -- Got uploadURL %s - Upload Authorization %s",
					$this->engineLogName, $this->uploadUrl->uploadUrl, $this->uploadUrl->authorizationToken
				));
			}
			catch (Exception $e)
			{
				Factory::getLog()->debug(sprintf(
					"%s -- Failed to initialize multipart upload of %s", $this->engineLogName, $sourceFile
				));

				throw new RuntimeException('Multipart upload cannot be initialised.', 500, $e);
			}
		}
		else
		{
			Factory::getLog()
				->debug(sprintf(
					"%s -- Continuing multipart upload of %s (fileId: %s –– Part number %d)",
					$this->engineLogName, $sourceFile, $this->fileId, $this->partNumber
				));
		}

		// Upload a chunk
		$mustFinalize = false;

		try
		{
			$partSize          = $this->getPartSizeForFile($sourceFile);
			$fileInfo          = $connector->uploadPart($this->uploadUrl, $sourceFile, $this->partNumber, $partSize);
			$this->sha1Parts[] = $fileInfo->contentSha1;
			$this->partNumber++;
		}
		catch (UnexpectedHTTPStatus $e)
		{
			/**
			 * If we get an HTTP 500 or 503 it means that the BackBlaze B2 storage pod is full. In this case B2 tells us
			 * to request a new upload URL (which will be in another pod) and retry the upload. Rinse and repeat until
			 * the upload completes.
			 *
			 * Implementation:
			 *
			 * - If the unexpected HTTP status is not 500 or 503 I need to rethrow the exception. In this case something
			 *   actually went wrong and we need to stop.
			 * - I need to notify the user that no, the chunk did not upload.
			 * - I DO NOT need to roll back the current chunk number or remove its stored SHA1 from the cache. Remember,
			 *   we failed at $connector->uploadPart which comes BEFORE we modify $this->sha1Parts and $this->partNumber
			 * - I do need to get a new upload URL with the stored file ID.
			 * - I need to return false, indicating we have more work to do here.
			 */
			if (!in_array($e->getCode(), [500, 503]))
			{
				throw $e;
			}

			Factory::getLog()
				->debug(sprintf(
					"%s -- BackBlaze B2 storage pod full (BackBlaze B2 returned HTTP %u). Getting new part upload URL. The upload will resume later.",
					$this->engineLogName, $e->getCode()
				));

			// Get a new upload URL
			$this->uploadUrl = $connector->getPartUploadUrl($this->fileId);

			return false;
		}
		catch (OutOfBoundsException $e)
		{
			$mustFinalize = true;
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf(
				"%s -- Multipart upload of %s has failed.", $this->engineLogName, $sourceFile
			));

			// Reset the multipart markers in temporary storage
			$akeebaConfig = Factory::getConfiguration();
			$akeebaConfig->set($this->volatileKeyPrefix . 'fileId', null);
			$akeebaConfig->set($this->volatileKeyPrefix . 'uploadUrl', null);
			$akeebaConfig->set($this->volatileKeyPrefix . 'partNumber', null);
			$akeebaConfig->set($this->volatileKeyPrefix . 'sha1Parts', null);

			throw new RuntimeException(sprintf(
				"Upload cannot proceed. %s returned an error.", $this->engineLogName
			), 500, $e);
		}

		// When we are done uploading we have to finalize
		if ($mustFinalize)
		{
			$count = count($this->sha1Parts);

			Factory::getLog()->debug(sprintf(
				"%s -- Finalising multipart upload of %s (fileId: %s –– %s parts in total)",
				$this->engineLogName, $sourceFile, $this->fileId, $count
			));

			$fileInfo = $connector->finishUpload($this->fileId, $this->sha1Parts);

			Factory::getLog()->debug(sprintf(
				"%s -- Finalised multipart upload of %s (fileId: %s)",
				$this->engineLogName, $sourceFile, $fileInfo->fileId
			));

			$this->fileId     = null;
			$this->uploadUrl  = null;
			$this->partNumber = null;
			$this->sha1Parts  = [];
		}

		// Save the internal tracking variables
		$akeebaConfig     = Factory::getConfiguration();
		$uploadURLAsArray = is_null($this->uploadUrl) ? [] : $this->uploadUrl->toArray();

		$akeebaConfig->set($this->volatileKeyPrefix . 'fileId', $this->fileId);
		$akeebaConfig->set($this->volatileKeyPrefix . 'uploadUrl', $uploadURLAsArray);
		$akeebaConfig->set($this->volatileKeyPrefix . 'partNumber', $this->partNumber);
		$akeebaConfig->set($this->volatileKeyPrefix . 'sha1Parts', json_encode($this->sha1Parts));

		// If I have an upload ID I have to do more work
		if (is_string($this->fileId) && !empty($this->fileId))
		{
			return false;
		}

		// In any other case I'm done uploading the file
		return true;
	}

	/**
	 * Perform a single-step upload of a file
	 *
	 * @param   string              $bucketId    The bucket ID to upload to
	 * @param   string              $remoteKey   The remote filename
	 * @param   string              $sourceFile  The full path to the local source file
	 * @param   ConnectorBackblaze  $connector   The S3 client object instance
	 *
	 * @return  bool  True when we're done uploading, false if we have more parts
	 *
	 * @throws  Exception  When an error occurs
	 */
	private function simpleUpload($bucketId, $remoteKey, $sourceFile, ConnectorBackblaze $connector)
	{
		Factory::getLog()->debug(sprintf(
			"%s -- Single part upload of %s", $this->engineLogName, basename($sourceFile)
		));

		$tries = 0;

		while (true)
		{
			$tries++;

			try
			{
				$connector->uploadSingleFile($bucketId, $remoteKey, $sourceFile);
			}
			catch (UnexpectedHTTPStatus $e)
			{
				/**
				 * If we get an HTTP 500 or 503 it means that the BackBlaze B2 storage pod is full. In this case B2 tells us
				 * to request a new upload URL (which will be in another pod) and retry the upload. Rinse and repeat until
				 * the upload completes.
				 *
				 * Implementation:
				 *
				 * - If this is the third try we give up (rethrow the exception). Something is actually wrong with B2.
				 * - If the unexpected HTTP status is not 500 or 503 I need to rethrow the exception. In this case
				 *   something actually went wrong and we need to stop.
				 * - I need to notify the user that no, the chunk did not upload.
				 * - I DO NOT need to roll back the current chunk number or remove its stored SHA1 from the cache. Remember,
				 *   we failed at $connector->uploadPart which comes BEFORE we modify $this->sha1Parts and $this->partNumber
				 * - I do need to get a new upload URL with the stored file ID.
				 * - I need to return false, indicating we have more work to do here.
				 */
				if (($tries >= 3) || !in_array($e->getCode(), [500, 503]))
				{
					throw $e;
				}

				Factory::getLog()
					->debug(sprintf(
						"%s -- BackBlaze B2 storage pod full (BackBlaze B2 returned HTTP %u). Retrying upload (attempt %d of a maximum of 2).",
						$this->engineLogName, $e->getCode(), $tries
					));

				continue;
			}

			break;
		}

		return true;
	}

	/**
	 * Get the configuration information for this post-processing engine
	 *
	 * @return  array
	 */
	private function getEngineConfiguration()
	{
		$akeebaConfig = Factory::getConfiguration();

		return [
			'accountId'        => $akeebaConfig->get('engine.postproc.backblaze.accountId', ''),
			'applicationKey'   => $akeebaConfig->get('engine.postproc.backblaze.applicationKey', ''),
			'disableMultipart' => $akeebaConfig->get('engine.postproc.backblaze.disableMultipart', 0),
			'bucket'           => $akeebaConfig->get('engine.postproc.backblaze.bucket', null),
			'directory'        => $akeebaConfig->get('engine.postproc.backblaze.directory', null),
			'chunkInMB'        => $akeebaConfig->get('engine.postproc.backblaze.chunk_upload_size', null),
		];
	}

	/**
	 * Get the bucket ID, fetching it from BackBlaze if it's not already populated
	 *
	 * @return  string  The bucket ID
	 *
	 * @throws  BackblazeBaseException  When fetching the bucket ID is impossible
	 * @throws  Exception  When we cannot get a connector object
	 */
	private function getBucketId()
	{
		if (!empty($this->bucketId))
		{
			return $this->bucketId;
		}

		$akeebaConfig   = Factory::getConfiguration();
		$engineConfig   = $this->getEngineConfiguration();
		$bucket         = $engineConfig['bucket'];
		$bucket         = str_replace('/', '', $bucket);
		$connector      = $this->getConnector();
		$this->bucketId = $connector->getBucketId($bucket);
		$akeebaConfig->set($this->volatileKeyPrefix . 'bucketId', $this->bucketId);

		return $this->bucketId;
	}

	/**
	 * Get the applicable part size for a given file. The part size cannot be smaller than the absolute minimum part
	 * size reported by Backblaze (typically 5MB). It also cannot be smaller than the file size divided by 10,000 as
	 * Backblaze will only allow us to upload up to 10,000 parts. This algorithm will try to use the user selected
	 * part size unless it is smaller than these hard requirements.
	 *
	 * Finally note that the part size is cached in volatile storage so that subsequent queries about it will not result
	 * in a performance penalty.
	 *
	 * @param   string  $sourceFile  The local file we want to figure out the part size for
	 *
	 * @return  int
	 *
	 * @throws  Exception  If getting the connector object is not possible
	 */
	private function getPartSizeForFile($sourceFile)
	{
		$akeebaConfig    = Factory::getConfiguration();
		$savedSourceFile = $akeebaConfig->get($this->volatileKeyPrefix . 'partSizeFile', null);
		$savedPartSize   = $akeebaConfig->get($this->volatileKeyPrefix . 'partSizeValue', null);

		if ($savedSourceFile == $sourceFile)
		{
			return $savedPartSize;
		}

		// Get the part size. Must be <= 100 MB
		$engineConfig = $this->getEngineConfiguration();
		$connector    = $this->getConnector();
		$minPartSize  = $connector->getAccountInformation()->absoluteMinimumPartSize;
		$partSize     = min($engineConfig['chunkInMB'], 100);
		$partSize     = $partSize * 1024 * 1024;
		$partSize     = max($minPartSize, $partSize);

		clearstatcache(false, $sourceFile);
		$fileSize = @filesize($sourceFile);

		/**
		 * Backblaze supports up to 10000 parts. We have to try increasing the part size until we're sure our  file
		 * will upload in a number of parts that's less than that.
		 */
		while ($fileSize / $partSize > 10000)
		{
			// Increase by 5M in each step
			$partSize += 5242880;
		}

		$akeebaConfig->set($this->volatileKeyPrefix . 'partSizeFile', $sourceFile);
		$akeebaConfig->set($this->volatileKeyPrefix . 'partSizeValue', $partSize);

		return $partSize;
	}
}
