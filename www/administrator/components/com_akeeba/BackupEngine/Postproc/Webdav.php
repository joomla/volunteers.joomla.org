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
use Akeeba\Engine\Postproc\Connector\Davclient as ConnectorDavclient;
use Akeeba\Engine\Postproc\Exception\BadConfiguration;
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Exception;
use RuntimeException;

class Webdav extends Base
{
	/**
	 * WebDAV username
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * WebDAV password
	 *
	 * @var string
	 */
	protected $password;

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
	 * The key used for storing settings in the Configuration registry
	 *
	 * @var string
	 */
	protected $settingsKey = 'webdav';

	public function __construct()
	{
		$this->supportsDownloadToBrowser = false;
		$this->supportsDelete            = true;
		$this->supportsDownloadToFile    = true;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		$basename  = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;

		try
		{
			$this->putFile($localFilepath, $basename);
		}
		catch (Exception $e)
		{
			// Upload failed. Let's log the failure first.
			Factory::getLog()->debug(sprintf("%s - WebDAV upload failed, %s: %s", __METHOD__, $e->getCode(), $e->getMessage()));

			// Increase the try counter
			$this->tryCount++;

			// However, if we've already retried twice, we stop retrying and call it a failure
			if ($this->tryCount > 2)
			{
				Factory::getLog()->debug(sprintf("%s - Maximum number of retries exceeded. The upload has failed.", __METHOD__));

				throw new RuntimeException('Uploading to WebDAV failed.', 500, $e);
			}

			Factory::getLog()->debug(sprintf("%s - The upload will be retried", __METHOD__));

			return false;
		}

		// Upload complete. Reset the retry counter.
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

		/** @var ConnectorDavclient $connector */
		$connector = $this->getConnector();
		$try       = 0;
		$handle    = null;

		while ($try < 2)
		{
			try
			{
				$handle = fopen($localFile, 'w+');

				if ($handle === false)
				{
					throw new RuntimeException(sprintf('Can not open file %s for writing.', $localFile));
				}

				$connector->request('GET', $remotePath, $handle);

				return;
			}
			catch (Exception $e)
			{
				if ($try > 0)
				{
					throw $e;
				}

				$try++;
			}
			finally
			{
				$this->conditionalFileClose($handle);
			}
		}

		throw new RuntimeException('WebDAV download failed without a reason we can report. This should never happen.');
	}

	public function delete($path)
	{
		/** @var ConnectorDavclient $connector */
		$connector = $this->getConnector();

		// Remove starting slash, or some servers will read it as an absolute path
		$path = '/' . ltrim($path, '/');
		$try  = 0;

		while ($try < 2)
		{
			try
			{
				$connector->request('DELETE', $path);
			}
			catch (Exception $e)
			{
				if ($try > 0)
				{
					throw $e;
				}

				$try++;
			}
		}

		throw new RuntimeException('WebDAV file delete failed without a reason we can report. This should never happen.');
	}

	/**
	 * Get the WebDAV settings from the backup profile configuration
	 *
	 * @return  array
	 *
	 * @throws  BadConfiguration  If there's a configuration issue
	 */
	protected function getSettings()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$username = trim($config->get('engine.postproc.' . $this->settingsKey . '.username', ''));
		$password = trim($config->get('engine.postproc.' . $this->settingsKey . '.password', ''));
		$url      = trim($config->get('engine.postproc.' . $this->settingsKey . '.url', ''));

		$defaultDirectory = $config->get('engine.postproc.' . $this->settingsKey . '.directory', '');
		$this->directory  = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Sanity checks
		if (empty($username) || empty($password))
		{
			throw new BadConfiguration('You have not linked Akeeba Backup with your WebDav account');
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

		$settings = [
			'baseUri'  => $url,
			'userName' => $username,
			'password' => $password,
		];

		// Last chance to modify the settings!
		$this->modifySettings($settings);

		return $settings;
	}

	protected function makeConnector()
	{
		$settings = $this->getSettings();

		$connector = new ConnectorDavclient($settings);
		$connector->addTrustedCertificates(AKEEBA_CACERT_PEM);

		return $connector;
	}

	/**
	 * Last chance to modify the settings before we create a WebDAV client.
	 *
	 * @param   array  $settings
	 *
	 * @return  void
	 */
	protected function modifySettings(array &$settings)
	{
		// No changes made in the default class. Only subclasses implement this.
	}

	/**
	 * Sends a file to WebDAV. It checks if the path to the file already exists. If not, it creates it.
	 *
	 * @param   string  $absolute_filename  The path to the local file which will be uploaded to WebDAV.
	 * @param   string  $basename           The name
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function putFile($absolute_filename, $basename)
	{
		/** @var ConnectorDavclient $connector */
		$connector = $this->getConnector();

		// Normalize double slashes
		$directory = str_replace('//', '/', $this->directory);
		$basename  = str_replace('//', '/', $basename);

		// Store the absolute remote path in the class property
		// Let's remove the starting slash, otherwise it read as an absolute path
		$this->remotePath = trim(trim($directory, '/') . '/' . trim($basename, '/'), '/');
		$this->remotePath = str_replace('//', '/', $this->remotePath);

		// A directory is supplied, let's check if it really exists or not
		if ($directory)
		{
			$checkPath = '';
			$parts     = explode('/', $directory);

			foreach ($parts as $part)
			{
				if (empty($part))
				{
					continue;
				}

				$checkPath .= $part . '/';

				try
				{
					Factory::getLog()->debug("Checking if the following remote path exists: " . $checkPath);

					$connector->propFind($checkPath, ['{DAV:}resourcetype']);
				}
				catch (Exception $e)
				{
					/**
					 * If the folder doesn't exists an error 404 is returned and I can suppress it. If the error code,
					 * however, is anything different then something went wrong and I have to re-throw the exception and
					 * let it bubble up.
					 */
					if ($e->getCode() != 404)
					{
						throw new $e;
					}

					Factory::getLog()->debug(sprintf("Remote path %s does not exists, I'm going to create it", $checkPath));

					// Folder doesn't exist, let's create it. Throws exception if it fails.
					$connector->request('MKCOL', $checkPath);

					Factory::getLog()->debug(sprintf("Remote path %s created", $checkPath));
				}
			}
		}

		$this->remotePath = '/' . ltrim($this->remotePath, '/');

		$result = $connector->request('PUT', $this->remotePath, file_get_contents($absolute_filename));

		return $result;
	}
}
