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
use Akeeba\Engine\Postproc\Connector\GoogleStorage as ConnectorGoogleStorage;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Googlestoragejson extends Base
{
	/**
	 * The retry count of this file (allow up to 2 retries after the first upload failure)
	 *
	 * @var int
	 */
	private $tryCount = 0;

	/**
	 * The currently configured bucket
	 *
	 * @var string
	 */
	private $bucket;

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
	private $isChunked = false;

	/**
	 * Chunk size (MB)
	 *
	 * @var int
	 */
	private $chunkSize = 10;

	/**
	 * The decoded Google Cloud JSON configuration file
	 *
	 * @var array
	 */
	private $config = [];

	public function __construct()
	{
		$this->supportsDownloadToBrowser = false;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		if ($this->tryCount >= 2)
		{
			throw new RuntimeException(sprintf(
				"%s - Maximum number of retries exceeded. The upload has failed. Check the log file for more information.",
				__METHOD__
			), 500);
		}

		// Do NOT remove. This is necessary to set up $this->directory used below.
		/** @var ConnectorGoogleStorage $connector */
		$connector = $this->getConnector();

		/**
		 * Store the absolute remote path in the object property.
		 *
		 * Something interesting. When the directory is empty (saving at the bucket's root) we MUST NOT use a slash
		 * prefix. This means that the remotePath /foobar.jpa IS WRONG. The correct is foobar.jpa without a leading
		 * slash. Hence the $directoryGlue variable below.
		 */
		$directory        = $this->directory;
		$basename         = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$directoryGlue    = empty($directory) ? '' : '/';
		$this->remotePath = $directory . $directoryGlue . $basename;

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		// Check if the size of the file is compatible with chunked uploading
		clearstatcache();
		$totalSize   = filesize($localFilepath);
		$isBigEnough = $this->isChunked ? ($totalSize > $this->chunkSize) : false;

		// Chunked uploads if the feature is enabled and the file is at least as big as the chunk size.
		if ($this->isChunked && $isBigEnough)
		{
			return $this->multipartUpload($localFilepath, $totalSize);
		}

		// Single part upload
		return $this->simpleUpload($localFilepath);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			// Ranges are not supported
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorGoogleStorage $connector */
		$connector = $this->getConnector();

		// Download the file
		$connector->download($this->bucket, $remotePath, $localFile);
	}

	public function delete($path)
	{
		/** @var ConnectorGoogleStorage $connector */
		$connector = $this->getConnector();

		$connector->delete($this->bucket, $path, true);
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		// Try to load and parse the Google JSON configuration
		$this->parseJsonConfig();

		$this->isChunked  = $config->get('engine.postproc.googlestoragejson.chunk_upload', true);
		$this->chunkSize  = $config->get('engine.postproc.googlestoragejson.chunk_upload_size', 10) * 1024 * 1024;
		$this->bucket     = $config->get('engine.postproc.googlestoragejson.bucket', null);
		$defaultDirectory = $config->get('engine.postproc.googlestoragejson.directory', '');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Environment checks
		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		if (!function_exists('openssl_sign') || !function_exists('openssl_get_md_methods'))
		{
			throw new BadConfiguration('The PHP module for OpenSSL integration is not enabled or openssl_sign() is disabled. Please contact your host and ask them to fix this issue for the version of PHP you are currently using on your site (PHP reports itself as version ' . PHP_VERSION . ').');
		}

		$openSSLAlgos = openssl_get_md_methods(true);

		if (!in_array('sha256WithRSAEncryption', $openSSLAlgos))
		{
			throw new BadConfiguration('The PHP module for OpenSSL integration does not support the sha256WithRSAEncryption signature algorithm. Please ask your host to compile BOTH a newer version of the OpenSSL library AND the OpenSSL module for PHP against this (new) OpenSSL library for the version of PHP you are currently using on your site (PHP reports itself as version ' . PHP_VERSION . ').');
		}

		// Fix the directory name, if required
		$this->directory = empty($this->directory) ? '' : $this->directory;
		$this->directory = trim($this->directory);
		$this->directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($this->directory), '/');
		$this->directory = Factory::getFilesystemTools()->replace_archive_name_variables($this->directory);
		$config->set('volatile.postproc.directory', $this->directory);

		return new ConnectorGoogleStorage($this->config['client_email'], $this->config['private_key']);
	}

	/**
	 * Tries to read the Google Cloud JSON credentials from the configuration.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  If the JSON credentials are missing or can not  be parsed.
	 */
	private function parseJsonConfig()
	{
		$config = Factory::getConfiguration();

		$hasJsonConfig = false;
		$jsonConfig    = trim($config->get('engine.postproc.googlestoragejson.jsoncreds', ''));
		$jsonConfig    = $this->fixStoredJSON($jsonConfig);

		if (!empty($jsonConfig))
		{
			$hasJsonConfig = true;
			$this->config  = @json_decode($jsonConfig, true);
		}

		if (empty($this->config))
		{
			$hasJsonConfig = false;
		}

		if ($hasJsonConfig && (
				!isset($this->config['type']) ||
				!isset($this->config['project_id']) ||
				!isset($this->config['private_key']) ||
				!isset($this->config['client_email'])
			)
		)
		{
			$hasJsonConfig = false;
		}

		if ($hasJsonConfig && (
				($this->config['type'] != 'service_account') ||
				(empty($this->config['project_id'])) ||
				(empty($this->config['private_key'])) ||
				(empty($this->config['client_email']))
			)
		)
		{
			$hasJsonConfig = false;
		}

		if (!$hasJsonConfig)
		{
			$this->config = [];
			throw new RuntimeException('You have not provided a valid Google Cloud JSON configuration (googlestorage.json) in the configuration page. As a result I cannot connect to Google Storage.');
		}
	}

	/**
	 * The Google JSON API file has the string literal "\n" inside the Private Key. However, the INI parser will
	 * unescape this into a newline character. This causes a newline character to appear inside a string literal in the
	 * JSON file, therefore rendering the JSON invalid.
	 *
	 * Before Akeeba Engine 6.3.4 we used to deal with that by using the PHP INI parser in INI_SCANNER_NORMAL mode.
	 * However, this caused some problems, e.g. with the sequence \$ being squashed to $. The correct solution is using
	 * INI_SCANNER_RAW. However, this does not expand \n in values which is something we need elsewhere in the Engine.
	 *
	 * We needed to do this because before 6.3.4 if your server had disabled parse_ini_string our PHP-based parser would
	 * yield different results than calling PHP's parse_ini_file(). This also meant that on these hosts Google Storage
	 * JSON API was broken.
	 *
	 * The only solution is having this method which recodes the private key in a way that the JSON is valid and the
	 * private key is also usable with Google's API.
	 *
	 * @param   string  $jsonConfig
	 *
	 * @return  string
	 */
	private function fixStoredJSON($jsonConfig)
	{
		// Remove all newlines
		$jsonConfig = str_replace("\n", '', $jsonConfig);

		// Extract the private key
		$startPos = strpos($jsonConfig, '-----BEGIN PRIVATE KEY-----');
		$endPos   = strpos($jsonConfig, '-----END PRIVATE KEY-----') + 25;
		$pk       = substr($jsonConfig, $startPos, $endPos - $startPos);

		// Recode the private key
		$innerPK = trim(substr($pk, 27, -25));
		$innerPK = implode("\\n", str_split($innerPK, 64));
		$pk      = "-----BEGIN PRIVATE KEY-----\\n" . $innerPK . "\\n-----END PRIVATE KEY-----";
		$pk      = str_replace("\\n\\n", "\\n", $pk);

		// Assemble a usable JSON string
		return rtrim(substr($jsonConfig, 0, $startPos)) . $pk . ltrim(substr($jsonConfig, $endPos));
	}

	/**
	 * Performs a multipart (chunked) upload.
	 *
	 * @param   string  $localFilepath  The path to the local file we'll be uploading.
	 * @param   int     $totalSize      The total size of the file, in bytes.
	 *
	 * @return  bool  True if the upload is complete, false if more work is necessary
	 * @throws  Exception  If an upload error occurs
	 */
	private function multipartUpload($localFilepath, $totalSize)
	{
		/** @var ConnectorGoogleStorage $connector */
		$connector = $this->getConnector();

		// Get a reference to the engine configuration
		$config = Factory::getConfiguration();

		Factory::getLog()->debug(sprintf(
			"%s - Using chunked upload, part size %d",
			__METHOD__, $this->chunkSize
		));

		$offset    = $config->get('volatile.engine.postproc.googlestoragejson.offset', 0);
		$upload_id = $config->get('volatile.engine.postproc.googlestoragejson.upload_id', null);

		if (empty($upload_id))
		{
			Factory::getLog()->debug(sprintf(
				"%s - Creating new upload session",
				__METHOD__
			));

			try
			{
				$storageClass = $config->get('engine.postproc.googlestoragejson.storageclass', null, false);
				$upload_id    = $connector->createUploadSession($this->bucket, $this->remotePath, $localFilepath, $storageClass);
			}
			catch (Exception $e)
			{
				// Note: do not pass the parent exception; it's a simple RuntimeException
				throw new RuntimeException(sprintf("The upload session for remote file %s cannot be created", $this->remotePath));
			}

			Factory::getLog()->debug(sprintf(
				"%s - New upload session $upload_id",
				__METHOD__
			));

			$config->set('volatile.engine.postproc.googlestoragejson.upload_id', $upload_id);
		}

		try
		{
			if (empty($offset))
			{
				$offset = 0;
			}

			Factory::getLog()->debug(sprintf(
				"%s - Uploading chunked part",
				__METHOD__
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

			// Let's retry
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				throw new RuntimeException(sprintf(
					"%s - Maximum number of retries exceeded. The upload has failed.",
					__METHOD__
				), 500, $e);
			}

			Factory::getLog()->debug(sprintf(
				"%s - Error detected, retrying chunk upload",
				__METHOD__
			));

			return false;
		}

		// Are we done uploading?
		$nextOffset = $offset + $this->chunkSize - 1;

		if (isset($result['name']) || ($nextOffset > $totalSize))
		{
			Factory::getLog()->debug(sprintf(
				"%s - Chunked upload is now complete", __METHOD__
			));

			$config->set('volatile.engine.postproc.googlestoragejson.offset', null);
			$config->set('volatile.engine.postproc.googlestoragejson.upload_id', null);

			$this->tryCount = 0;

			return true;
		}

		// Otherwise, continue uploading
		$config->set('volatile.engine.postproc.googlestoragejson.offset', $offset + $this->chunkSize);

		return false;
	}

	/**
	 * Performs a single part upload
	 *
	 * @param   string  $localFilepath
	 *
	 * @return  bool  True if the upload is complete, false if more work is necessary
	 * @throws  Exception  If an upload error occurs
	 */
	private function simpleUpload($localFilepath)
	{
		/** @var ConnectorGoogleStorage $connector */
		$connector = $this->getConnector();

		try
		{
			Factory::getLog()->debug(sprintf("%s - Performing simple upload.", __METHOD__));

			$storageClass = Factory::getConfiguration()->get('engine.postproc.googlestoragejson.storageclass', null, false);

			$connector->simpleUpload($this->bucket, $this->remotePath, $localFilepath, $storageClass);
		}
		catch (Exception $e)
		{
			Factory::getLog()->debug(sprintf("%s - Simple upload failed, %s: %s", __METHOD__, $e->getCode(), $e->getMessage()));

			// Let's retry
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				throw new RuntimeException(sprintf("%s - Maximum number of retries exceeded. The upload has failed.", __METHOD__), 500, $e);
			}

			Factory::getLog()->debug(sprintf("%s - Error detected, retrying upload", __METHOD__));

			return false;
		}

		// Upload complete. Reset the retry counter.
		$this->tryCount = 0;

		return true;
	}
}
