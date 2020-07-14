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
use Akeeba\Engine\Postproc\Connector\Idrivesync as ConnectorIdrivesync;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Idrivesync extends Base
{
	/**
	 * The currently configured directory
	 *
	 * @var string
	 */
	private $directory;

	public function __construct()
	{
		$this->supportsDownloadToBrowser = false;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var ConnectorIdrivesync $connector */
		$connector = $this->getConnector();
		$directory = $this->directory;

		// Store the absolute remote path in the class property
		$this->remotePath = $directory . '/' . basename($localFilepath);

		try
		{
			$connector->uploadFile($localFilepath, '/' . $directory . '/');
		}
		catch (Exception $e)
		{
			throw new RuntimeException('iDriveSync error -- Remote path: ' . $directory, 500, $e);
		}

		return true;
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			// Ranges are not supported
			throw new RangeDownloadNotSupported();
		}

		/** @var ConnectorIdrivesync $connector */
		$connector = $this->getConnector();

		$connector->downloadFile($remotePath, $localFile);
	}

	public function delete($path)
	{
		/** @var ConnectorIdrivesync $connector */
		$connector = $this->getConnector();

		$connector->deleteFile($path);
	}

	protected function makeConnector()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$username         = trim($config->get('engine.postproc.idrivesync.username', ''));
		$password         = trim($config->get('engine.postproc.idrivesync.password', ''));
		$pvtkey           = trim($config->get('engine.postproc.idrivesync.pvtkey', ''));
		$defaultDirectory = $config->get('engine.postproc.idrivesync.directory', '');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);
		$newendpoint      = $config->get('engine.postproc.idrivesync.newendpoint', false);

		// Sanity checks
		if (empty($username) || empty($password))
		{
			throw new BadConfiguration('You have not set up the connection to your iDriveSync account');
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

		try
		{
			return new ConnectorIdrivesync($username, $password, $pvtkey, $newendpoint);
		}
		catch (Exception $e)
		{
			throw new RuntimeException('iDriveSync initialization error', 500, $e);
		}
	}
}
