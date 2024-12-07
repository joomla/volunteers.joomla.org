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
use Akeeba\Engine\Util\Transfer\Sftp as SftpTransfer;
use Akeeba\Engine\Util\Transfer\TransferInterface;
use RuntimeException;

class Sftp extends Ftp
{
	public function __construct()
	{
		parent::__construct();

		$this->supportsDownloadToBrowser = false;
		$this->engineKey                 = 'engine.postproc.sftp.';
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
		$privKey          = $config->get($this->engineKey . 'privkey', '');
		$pubKey           = $config->get($this->engineKey . 'pubkey', '');
		$defaultDirectory = $config->get($this->engineKey . 'initial_directory', '');
		$directory        = $config->get('volatile.postproc.directory', $defaultDirectory);

		// Process the initial directory
		$directory = '/' . ltrim(trim($directory), '/');
		$directory = Factory::getFilesystemTools()->replace_archive_name_variables($directory);
		$config->set('volatile.postproc.directory', $directory);

		// Try to automatically fix protocol in the hostname
		if (strtolower(substr($host, 0, 7)) == 'sftp://')
		{
			Factory::getLog()->warning('YOU ARE *** N O T *** SUPPOSED TO ENTER THE sftp:// PROTOCOL PREFIX IN THE SFTP HOSTNAME FIELD OF THE Upload to Remote SFTP POST-PROCESSING ENGINE.');
			Factory::getLog()->warning('I am trying to fix your bad configuration setting, but the backup might fail anyway. You MUST fix this in your configuration.');

			$host = substr($host, 7);
		}

		return [
			'host'       => $host,
			'port'       => $port,
			'username'   => $username,
			'password'   => $password,
			'directory'  => $directory,
			'privateKey' => $privKey,
			'publicKey'  => $pubKey,
			'subdir'     => null,
		];
	}

	protected function makeConnector()
	{
		Factory::getLog()->debug(__CLASS__ . ':: Connecting to remote SFTP');

		$options    = $this->getConfig();
		$sftphandle = new SftpTransfer($options);

		if (!$this->sftp_chdir($options['directory'], $sftphandle))
		{
			throw new RuntimeException(sprintf(
				"Invalid initial directory %s for the remote SFTP server",
				$options['directory']
			));
		}

		return $sftphandle;
	}

	/**
	 * Changes to the requested directory in the remote server. You give only the
	 * path relative to the initial directory and it does all the rest by itself,
	 * including doing nothing if the remote directory is the one we want. If the
	 * directory doesn't exist, it creates it.
	 *
	 * @param   string             $dir
	 * @param   TransferInterface  $sftphandle
	 *
	 * @return  boolean
	 */
	protected function sftp_chdir($dir, &$sftphandle)
	{
		// Calculate "real" (absolute) SFTP path
		$result = $sftphandle->isDir($dir);

		if ($result === false)
		{
			// The directory doesn't exist, let's try to create it...
			if (!$this->makeDirectory($dir, $sftphandle))
			{
				return false;
			}
		}

		// Update the private "current remote directory" variable
		return true;
	}

	/**
	 * Creates a nested directory structure on the remote SFTP server
	 *
	 * @param   string             $dir
	 * @param   TransferInterface  $sftphandle
	 *
	 * @return  boolean
	 */
	protected function makeDirectory($dir, &$sftphandle)
	{
		$alldirs     = explode('/', $dir);
		$previousDir = '';

		foreach ($alldirs as $curdir)
		{
			// Avoid empty dir
			if (!$curdir)
			{
				continue;
			}

			$check = $previousDir . '/' . $curdir;

			if (!$sftphandle->isDir($check))
			{
				if ($sftphandle->mkdir($check) === false)
				{
					throw new RuntimeException('Could not create SFTP directory ' . $check);
				}
			}

			$previousDir = $check;
		}

		return true;
	}
}
