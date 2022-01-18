<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Archiver;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Util\Transfer\Ftp;
use Akeeba\Engine\Util\Transfer\FtpCurl;
use RuntimeException;

/**
 * Direct Transfer Over FTP Over cURL archiver class
 *
 * Transfers the files to a remote FTP server instead of putting them in
 * an archive
 *
 */
class Directftpcurl extends Directftp
{
	/** @var bool Could we connect to the server? */
	public $connect_ok = false;
	/** @var Ftp FTP resource handle */
	protected $ftpTransfer;
	/** @var string FTP hostname */
	protected $host;
	/** @var string FTP port */
	protected $port;
	/** @var string FTP username */
	protected $user;
	/** @var string FTP password */
	protected $pass;
	/** @var bool Should we use FTP over SSL? */
	protected $usessl;
	/** @var bool Should we use passive FTP? */
	protected $passive;
	/** @var bool Enable the passive mode workaround? */
	protected $passiveWorkaround = true;
	/** @var string FTP initial directory */
	protected $initdir;

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

		$this->host              = $registry->get('engine.archiver.directftpcurl.host', '');
		$this->port              = $registry->get('engine.archiver.directftpcurl.port', '21');
		$this->user              = $registry->get('engine.archiver.directftpcurl.user', '');
		$this->pass              = $registry->get('engine.archiver.directftpcurl.pass', '');
		$this->initdir           = $registry->get('engine.archiver.directftpcurl.initial_directory', '');
		$this->usessl            = $registry->get('engine.archiver.directftpcurl.ftps', false);
		$this->passive           = $registry->get('engine.archiver.directftpcurl.passive_mode', true);
		$this->passiveWorkaround = $registry->get('engine.archiver.directftpcurl.passive_mode_workaround', true);

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

		if (isset($options['initdir']))
		{
			$this->initdir = $options['initdir'];
		}

		if (isset($options['usessl']))
		{
			$this->usessl = $options['usessl'];
		}

		if (isset($options['passive']))
		{
			$this->passive = $options['passive'];
		}

		if (isset($options['passive_fix']))
		{
			$this->passiveWorkaround = $options['passive_fix'] ? true : false;
		}

		// You can't fix stupid, but at least you get to shout at them
		if (strtolower(substr($this->host, 0, 6)) == 'ftp://')
		{
			Factory::getLog()->warning('YOU ARE *** N O T *** SUPPOSED TO ENTER THE ftp:// PROTOCOL PREFIX IN THE FTP HOSTNAME FIELD OF THE DirectFTP ARCHIVER ENGINE.');
			Factory::getLog()->warning('I am trying to fix your bad configuration setting, but the backup might fail anyway. You MUST fix this in your configuration.');
			$this->host = substr($this->host, 6);
		}

		$this->connect_ok = $this->connectFTP();

		Factory::getLog()->debug(__CLASS__ . " :: FTP connection status: " . ($this->connect_ok ? 'success' : 'FAIL'));
	}

	/**
	 * Tries to connect to the remote FTP server and change into the initial directory
	 *
	 * @return bool True is connection successful, false otherwise
	 *
	 * @throws RuntimeException
	 */
	protected function connectFTP()
	{
		Factory::getLog()->debug('Connecting to remote FTP');

		$options = [
			'host'        => $this->host,
			'port'        => $this->port,
			'username'    => $this->user,
			'password'    => $this->pass,
			'directory'   => $this->initdir,
			'ssl'         => $this->usessl,
			'passive'     => $this->passive,
			'passive_fix' => $this->passiveWorkaround,
		];

		$this->ftpTransfer = new FtpCurl($options);

		return true;
	}
}
