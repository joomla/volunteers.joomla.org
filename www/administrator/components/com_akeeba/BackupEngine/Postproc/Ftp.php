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
use Akeeba\Engine\Postproc\Exception\RangeDownloadNotSupported;
use Akeeba\Engine\Util\Transfer\Ftp as TransferFtp;
use Exception;
use RuntimeException;

class Ftp extends Base
{
	protected $engineKey = 'engine.postproc.ftp.';

	public function __construct()
	{
		$this->supportsDelete            = true;
		$this->supportsDownloadToBrowser = true;
		$this->supportsDownloadToFile    = true;
	}

	public function processPart($localFilepath, $remoteBaseName = null)
	{
		/** @var TransferFtp $connector */
		$connector        = $this->getConnector();
		$config           = $this->getConfig();
		$subDirectory     = $config['subdir'];
		$initialDirectory = $config['directory'];

		// If supplied, change to the subdirectory
		if ($subDirectory)
		{
			$subDirectory = trim(Factory::getFilesystemTools()->replace_archive_name_variables($subDirectory), '/');

			if (!$connector->isDir($initialDirectory . '/' . $subDirectory))
			{
				// Got an error? This means that the directory doesn't exist, let's try to create it
				if (!$connector->mkdir($initialDirectory . '/' . $subDirectory))
				{
					// Ok, I really can't do anything, let's stop here
					throw new RuntimeException(sprintf(
						"Could not create the subdirectory %s in the remote FTP server", $subDirectory
					));
				}

				// Let's move into the new directory
				if (!$connector->isDir($initialDirectory . '/' . $subDirectory))
				{
					// This should never happen, anyway better be safe than sorry
					throw new RuntimeException(sprintf(
						"Could not move into the subdirectory %s in the remote FTP server", $subDirectory
					));
				}
			}
		}

		Factory::getLog()->debug(sprintf("%s:: Starting FTP upload of $localFilepath", __CLASS__));

		$absoluteRemoteDirectory = $initialDirectory;

		if (substr($initialDirectory, -1) == '/')
		{
			$absoluteRemoteDirectory = substr($initialDirectory, 0, strlen($initialDirectory) - 1);
		}

		if (!empty($subDirectory))
		{
			$absoluteRemoteDirectory .= '/' . $subDirectory;
		}

		$basename               = empty($remoteBaseName) ? basename($localFilepath) : $remoteBaseName;
		$absoluteRemoteFilepath = $absoluteRemoteDirectory . '/' . $basename;
		$this->remotePath       = $absoluteRemoteFilepath;
		$res                    = $connector->upload($localFilepath, $absoluteRemoteFilepath);

		if (!$res)
		{
			if (is_readable($localFilepath))
			{
				throw new RuntimeException(sprintf("Uploading %s has failed.", $localFilepath));
			}

			throw new RuntimeException(sprintf("Uploading %s has failed because the file is unreadable.", $localFilepath));
		}

		return true;
	}

	public function delete($path)
	{
		/** @var TransferFtp $connector */
		$connector = $this->getConnector();

		try
		{
			$connector->delete($path);
		}
		catch (Exception $e)
		{
			throw new RuntimeException(sprintf('Deleting %s failed.', $path), 500, $e);
		}
	}

	public function downloadToFile($remotePath, $localFile, $fromOffset = null, $length = null)
	{
		if (!is_null($fromOffset))
		{
			throw new RangeDownloadNotSupported();
		}

		/** @var TransferFtp $connector */
		$connector = $this->getConnector();

		$connector->download($remotePath, $localFile);
	}

	public function downloadToBrowser($remotePath)
	{
		$config = $this->getConfig();

		$host = $config['host'];
		$port = $config['port'];
		$user = $config['username'];
		$pass = $config['password'];
		$ssl  = $config['ssl'];
		$uri  = $ssl ? 'ftps://' : 'ftp://';

		if ($user && $pass)
		{
			$uri .= urlencode($user) . ':' . urlencode($pass) . '@';
		}

		$uri .= $host;

		if ($port && ($port != 21))
		{
			$uri .= ':' . $port;
		}

		if (substr($remotePath, 0, 1) != '/')
		{
			$uri .= '/';
		}

		$uri .= $remotePath;

		return $uri;
	}

	/**
	 * Return the engine configuration
	 *
	 * @return array
	 */
	protected function getConfig()
	{
		// Retrieve engine configuration data
		$config = Factory::getConfiguration();

		$host             = $config->get($this->engineKey . 'host', '');
		$port             = $config->get($this->engineKey . 'port', 21);
		$username         = $config->get($this->engineKey . 'user', '');
		$password         = $config->get($this->engineKey . 'pass', 0);
		$defaultDirectory = $config->get($this->engineKey . 'initial_directory', '');
		$directory        = $config->get('volatile.postproc.directory', $defaultDirectory);
		$subdir           = trim($config->get($this->engineKey . 'subdirectory', ''), '/');
		$ssl              = $config->get($this->engineKey . 'ftps', 0) == 0 ? false : true;
		$passive          = $config->get($this->engineKey . 'passive_mode', 0) == 0 ? false : true;
		$workaround       = $config->get($this->engineKey . 'passive_mode_workaround', null);

		// Process the initial directory
		$directory = '/' . ltrim(trim($directory), '/');
		$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);
		$config->set('volatile.postproc.directory', $directory);

		// Try to automatically fix protocol in the hostname
		if (strtolower(substr($host, 0, 6)) == 'ftp://')
		{
			Factory::getLog()->warning('YOU ARE *** N O T *** SUPPOSED TO ENTER THE ftp:// PROTOCOL PREFIX IN THE FTP HOSTNAME FIELD OF THE Upload to Remote FTP POST-PROCESSING ENGINE.');
			Factory::getLog()->warning('I am trying to fix your bad configuration setting, but the backup might fail anyway. You MUST fix this in your configuration.');
			$host = substr($host, 6);
		}

		return [
			'host'        => $host,
			'port'        => $port,
			'username'    => $username,
			'password'    => $password,
			'directory'   => $directory,
			'ssl'         => $ssl,
			'passive'     => $passive,
			'passive_fix' => $workaround,
			'subdir'      => $subdir,
		];
	}

	protected function makeConnector()
	{
		Factory::getLog()->debug(__CLASS__ . ':: Connecting to remote FTP');

		$config = $this->getConfig();

		return new TransferFtp($config);
	}
}
