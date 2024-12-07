<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Archiver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Transfer\Sftp;
use RuntimeException;

/**
 * Direct Transfer Over SFTP archiver class
 *
 * Transfers the files to a remote SFTP server instead of putting them in
 * an archive
 *
 */
class Directsftp extends Base
{
	/** @var bool Could we connect to the server? */
	public $connect_ok = false;
	/** @var Sftp SFTP resource handle */
	private $sftpTransfer = false;
	/** @var string SFTP hostname */
	private $host;
	/** @var string SFTP port */
	private $port;
	/** @var string SFTP username */
	private $user;
	/** @var string SFTP password */
	private $pass;
	/** @var string Private key file */
	private $privkey;
	/** @var string Private key file */
	private $pubkey;
	/** @var string FTP initial directory */
	private $initdir;

	/**
	 * Initialises the archiver class, seeding the remote installation
	 * from an existent installer's JPA archive.
	 *
	 * @param   string  $targetArchivePath  Absolute path to the generated archive (ignored in this class)
	 * @param   array   $options            A named key array of options (optional)
	 *
	 * @return  void
	 */
	public function initialize($targetArchivePath, $options = [])
	{
		Factory::getLog()->debug(__CLASS__ . " :: new instance");

		$registry = Factory::getConfiguration();

		$this->host    = $registry->get('engine.archiver.directsftp.host', '');
		$this->port    = $registry->get('engine.archiver.directsftp.port', '22');
		$this->user    = $registry->get('engine.archiver.directsftp.user', '');
		$this->pass    = $registry->get('engine.archiver.directsftp.pass', '');
		$this->privkey = $registry->get('engine.archiver.directsftp.privkey', '');
		$this->pubkey  = $registry->get('engine.archiver.directsftp.pubkey', '');
		$this->initdir = $registry->get('engine.archiver.directsftp.initial_directory', '');

		if (isset($options['host']))
		{
			$this->host = $options['host'];
		}

		if (isset($options['port']))
		{
			$this->port = $options['port'];
		}

		if (isset($options['user']))
		{
			$this->user = $options['user'];
		}

		if (isset($options['pass']))
		{
			$this->pass = $options['pass'];
		}

		if (isset($options['privkey']))
		{
			$this->privkey = $options['privkey'];
		}

		if (isset($options['pubkey']))
		{
			$this->pubkey = $options['pubkey'];
		}

		if (isset($options['initdir']))
		{
			$this->initdir = $options['initdir'];
		}

		// You can't fix stupid, but at least you get to shout at them
		if (strtolower(substr($this->host, 0, 7)) == 'sftp://')
		{
			Factory::getLog()->warning('YOU ARE *** N O T *** SUPPOSED TO ENTER THE sftp:// PROTOCOL PREFIX IN THE FTP HOSTNAME FIELD OF THE DirectSFTP ARCHIVER ENGINE.');
			Factory::getLog()->warning('I am trying to fix your bad configuration setting, but the backup might fail anyway. You MUST fix this in your configuration.');

			$this->host = substr($this->host, 7);
		}

		$this->connect_ok = $this->connectSFTP();

		Factory::getLog()->debug(__CLASS__ . " :: SFTP connection status: " . ($this->connect_ok ? 'success' : 'FAIL'));
	}

	/**
	 * Returns a string with the extension (including the dot) of the files produced
	 * by this class.
	 *
	 * @return string
	 */
	public function getExtension()
	{
		return '';
	}

	public function finalize()
	{
		// Nothing to do
	}

	/**
	 * "Magic" function called just before serialization of the object. Disconnects
	 * from the FTP server and allows PHP to serialize like normal.
	 *
	 * @return array The variables to serialize
	 */
	public function _onSerialize()
	{
		// Explicitally unset the sftpTransfer class so the destructor magic method is called (and the connection is closed)
		unset($this->sftpTransfer);

		return array_keys(get_object_vars($this));
	}

	/**
	 * Tries to connect to the remote SFTP server and change into the initial directory
	 *
	 * @return bool True is connection successful, false otherwise
	 */
	protected function connectSFTP()
	{
		Factory::getLog()->debug('Connecting to remote SFTP server');

		$options = [
			'host'       => $this->host,
			'port'       => $this->port,
			'username'   => $this->user,
			'password'   => $this->pass,
			'directory'  => $this->initdir,
			'privateKey' => $this->privkey,
			'publicKey'  => $this->pubkey,
		];

		$this->sftpTransfer = new Sftp($options);

		return true;
	}

	/**
	 * The most basic file transaction: add a single entry (file or directory) to
	 * the archive.
	 *
	 * @param   bool    $isVirtual         If true, the next parameter contains file data instead of a file name
	 * @param   string  $sourceNameOrData  Absolute file name to read data from or the file data itself is $isVirtual is
	 *                                     true
	 * @param   string  $targetName        The (relative) file name under which to store the file in the archive
	 *
	 * @return boolean True on success, false otherwise
	 */
	protected function _addFile($isVirtual, &$sourceNameOrData, $targetName)
	{
		// Are we connected to a server?
		if (!$this->sftpTransfer)
		{
			if (!$this->connectSFTP())
			{
				return false;
			}
		}

		// See if it's a directory
		$isDir = $isVirtual ? false : is_dir($sourceNameOrData);

		if ($isDir)
		{
			// Just try to create the remote directory
			return $this->makeDirectory($targetName);
		}
		else
		{
			// We have a file we need to upload
			if ($isVirtual)
			{
				// Create a temporary file, upload, rename it
				$tempFileName = Factory::getTempFiles()->createRegisterTempFile();

				// Easy writing using file_put_contents
				if (@file_put_contents($tempFileName, $sourceNameOrData) === false)
				{
					throw new RuntimeException('Could not store virtual file ' . $targetName . ' to ' . $tempFileName . ' using file_put_contents() before uploading.');
				}

				// Upload the temporary file under the final name
				$res = $this->upload($tempFileName, $targetName);

				// Remove the temporary file
				Factory::getTempFiles()->unregisterAndDeleteTempFile($tempFileName, true);

				return $res;
			}
			else
			{
				// Upload a file
				return $this->upload($sourceNameOrData, $targetName);
			}
		}
	}

	protected function makeDirectory($dir)
	{
		$alldirs     = explode('/', $dir);
		$previousDir = substr($this->initdir, -1) == '/' ? substr($this->initdir, 0, strlen($this->initdir) - 1) : $this->initdir;
		$previousDir = substr($previousDir, 0, 1) == '/' ? $previousDir : '/' . $previousDir;

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;

			if (!@$this->sftpTransfer->isDir($check))
			{
				if ($this->sftpTransfer->mkdir($check) === false)
				{
					throw new RuntimeException('Could not create directory ' . $check);
				}
			}

			$previousDir = $check;
		}

		return true;
	}

	/**
	 * Uploads a file to the remote server
	 *
	 * @param $sourceName string The absolute path to the source local file
	 * @param $targetName string The relative path to the targer remote file
	 *
	 * @return bool True if successful
	 */
	protected function upload($sourceName, $targetName)
	{
		// Try to change into the remote directory, possibly creating it if it doesn't exist
		$dir = dirname($targetName);

		if (!$this->sftp_chdir($dir))
		{
			return false;
		}

		// Upload
		$realdir  = substr($this->initdir, -1) == '/' ? substr($this->initdir, 0, strlen($this->initdir) - 1) : $this->initdir;
		$realdir  .= '/' . $dir;
		$realdir  = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;
		$realname = $realdir . '/' . basename($targetName);

		$this->sftpTransfer->upload($sourceName, $realname);

		return true;
	}

	/**
	 * Changes to the requested directory in the remote server. You give only the
	 * path relative to the initial directory and it does all the rest by itself,
	 * including doing nothing if the remote directory is the one we want. If the
	 * directory doesn't exist, it creates it.
	 *
	 * @param $dir string The (realtive) remote directory
	 *
	 * @return bool True if successful, false otherwise.
	 */
	protected function sftp_chdir($dir)
	{
		// Calculate "real" (absolute) SFTP path
		$realdir = substr($this->initdir, -1) == '/' ? substr($this->initdir, 0, strlen($this->initdir) - 1) : $this->initdir;
		$realdir .= '/' . $dir;
		$realdir = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;

		if ($this->initdir == $realdir)
		{
			// Already there, do nothing
			return true;
		}

		$result = $this->sftpTransfer->isDir($realdir);

		if ($result === false)
		{
			// The directory doesn't exist, let's try to create it...
			if (!$this->makeDirectory($dir))
			{
				return false;
			}
		}

		return true;
	}
}
