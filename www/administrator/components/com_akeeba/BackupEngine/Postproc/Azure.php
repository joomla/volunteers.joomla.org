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
use Akeeba\Engine\Postproc\Connector\AzureModern\Connector as AzureConnector;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;

class Azure extends Base
{
	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
		$this->supportsDownloadToBrowser = true;
		$this->inlineDownloadToBrowser   = false;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		// Retrieve engine configuration data
		$config             = Factory::getConfiguration();
		$container          = $config->get('engine.postproc.azure.container', 0);
		$disableMultipart   = $config->get('engine.postproc.azure.chunk_upload', 1) == 0;
		$preferredChunkSize = $config->get('engine.postproc.azure.chunk_upload_size', 10) * 1048576;
		$directory          = $config->get('volatile.postproc.directory', null);
		$partList           = $config->get('volatile.postproc.partList', []);

		// Treat directory and place it in volatile storage if it's not already there
		if (is_null($directory))
		{
			$directory = $config->get('engine.postproc.azure.directory', '');
			$directory = trim($directory);
			$directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($directory), '/');
			$directory = empty($directory) ? '' : $directory;
			$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);
			$config->set('volatile.postproc.directory', $directory);
		}

		// Calculate relative remote filename
		$filename = basename($localFilepath);

		if (!empty($directory) && ($directory != '/'))
		{
			$filename = $directory . '/' . $filename;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $filename;

		// Get the connector
		/** @var AzureConnector $connector */
		$connector = $this->getConnector();

		// Get the total file size
		@clearstatcache($localFilepath);
		$fileSize = @filesize($localFilepath) ?: 0;

		// Get facts about multipart support
		$canDoSinglePart = $connector->getBestBlockSize($localFilepath, 0) === 0;
		$chunkSize       = $config->get('volatile.postproc.chunkSize', null);
		$chunkSize       = $chunkSize ?: $connector->getBestBlockSize($localFilepath, $preferredChunkSize);

		// Only allow $disableMultipart if I can do a single part upload.
		$disableMultipart = $disableMultipart && $canDoSinglePart;
		// Force disable multipart if the file size is under the chunk size.
		$disableMultipart = $disableMultipart || ($canDoSinglePart && $fileSize <= $chunkSize);

		$config->set('volatile.postproc.chunkSize', $chunkSize);

		// Simplest case: single part upload
		if ($disableMultipart)
		{
			Factory::getLog()->debug(sprintf('Azure BLOB Storage -- Using single part upload of %s', $localFilepath));

			$config->remove('volatile.postproc.directory');
			$config->remove('volatile.postproc.partList');
			$config->remove('volatile.postproc.chunkSize');

			$connector->putBlob($container, $filename, $localFilepath);

			return true;
		}

		// Multipart upload.
		$chunksProcessed = count($partList);
		$offset          = $chunksProcessed * $chunkSize;

		if (empty($offset))
		{
			Factory::getLog()->debug(
				sprintf(
					'Azure BLOB Storage -- Starting multipart upload of %s (total size %d, chunk size %d)',
					$localFilepath, $fileSize, $chunkSize
				)
			);
		}
		else
		{
			Factory::getLog()->debug(
				sprintf(
					'Azure BLOB Storage -- Continuing multipart upload of %s, chunk #%d, chunk size %d',
					$localFilepath, $chunksProcessed + 1, $chunkSize
				)
			);
		}

		$fp = @fopen($localFilepath, 'r');

		if ($fp === false)
		{
			// TODO Throw
		}

		if ($offset > 0)
		{
			fseek($fp, $offset);
		}

		$data      = fread($fp, $chunkSize);
		$lastChunk = feof($fp);

		fclose($fp);

		$partList[] = $connector->putBlock($container, $filename, $data);

		$config->set('volatile.postproc.partList', $partList);

		// If we have not reached EOF there's more work to do uploading this file
		if (!$lastChunk)
		{
			return false;
		}

		// If we're here, we have finished uploading all chunks. Let's tell Azure to stitch them together.
		Factory::getLog()->debug(sprintf('Azure BLOB Storage -- Finalising multipart upload of %s', $localFilepath));

		$connector->putBlockList($container, $filename, $partList);

		Factory::getLog()->debug(sprintf('Azure BLOB Storage -- Multipart uploading of %s is now complete', $localFilepath));

		// Teardown of volatile data and return marking everything as all done
		$config->remove('volatile.postproc.directory');
		$config->remove('volatile.postproc.partList');
		$config->remove('volatile.postproc.chunkSize');

		return true;
	}

	public function delete($path)
	{
		$connector = $this->getConnector();
		$config    = Factory::getConfiguration();
		$container = $config->get('engine.postproc.azure.container', 0);

		$connector->deleteBlob($container, $path);
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			// Ranges are not supported
			throw new RangeDownloadNotSupported();
		}

		/** @var AzureConnector $connector */
		$connector = $this->getConnector();
		$config    = Factory::getConfiguration();
		$container = $config->get('engine.postproc.azure.container', 0);

		$connector->getBlob($container, $remotePath, $localFile);
	}

	public function downloadToBrowser($remotePath)
	{
		/** @var AzureConnector $connector */
		$connector = $this->getConnector();
		$config    = Factory::getConfiguration();
		$container = $config->get('engine.postproc.azure.container', 0);

		return $connector->getSignedURL($container, $remotePath, 600);
	}

	protected function makeConnector()
	{
		$config    = Factory::getConfiguration();
		$account   = trim($config->get('engine.postproc.azure.account', ''));
		$key       = trim($config->get('engine.postproc.azure.key', ''));
		$container = $config->get('engine.postproc.azure.container', 0);
		$useSSL    = $config->get('engine.postproc.azure.usessl', 1) == 1;

		// Sanity checks
		if (!function_exists('curl_init'))
		{
			throw new BadConfiguration('cURL is not enabled, please enable it in order to post-process your archives');
		}

		if (empty($account))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure account name');
		}

		if (empty($key))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure account key');
		}

		if (empty($container))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure container name');
		}

		return new AzureConnector($account, $key, false, $useSSL);
	}
}
