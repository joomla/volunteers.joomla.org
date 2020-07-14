<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;


use Akeeba\Engine\Factory;
use Akeeba\Engine\Postproc\Connector\Azure as AzureConnector;
use Akeeba\Engine\Postproc\Connector\Azure\AzureStorage as AzureStorage;
use Akeeba\Engine\Postproc\Connector\Azure\Retrypolicy\None as AzureRetryNone;
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
		$config           = Factory::getConfiguration();
		$container        = $config->get('engine.postproc.azure.container', 0);
		$defaultDirectory = $config->get('engine.postproc.azure.directory', '');
		$directory        = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Treat directory and place it in volatile storage
		$directory = trim($directory);
		$directory = ltrim(Factory::getFilesystemTools()->TranslateWinPath($directory), '/');
		$directory = empty($directory) ? '' : $directory;
		$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);
		$config->set('volatile.postproc.directory', $directory);

		// Calculate relative remote filename
		$filename = basename($localFilepath);

		if (!empty($directory) && ($directory != '/'))
		{
			$filename = $directory . '/' . $filename;
		}

		// Store the absolute remote path in the class property
		$this->remotePath = $filename;

		// Connect and send
		$blob = $this->getConnector();

		$blob->putBlob($container, $filename, $localFilepath);

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

		// Sanity checks
		if (empty($account))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure account name');
		}

		if (empty($key))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure key');
		}

		if (empty($container))
		{
			throw new BadConfiguration('You have not set up your Microsoft Azure container');
		}

		$host       = ($account == 'devstoreaccount1') ? AzureStorage::URL_DEV_BLOB : AzureStorage::URL_CLOUD_BLOB;
		$connector  = new AzureConnector($host, $account, $key);
		$policyNone = new AzureRetryNone();

		$connector->setRetryPolicy($policyNone);

		return $connector;
	}
}
