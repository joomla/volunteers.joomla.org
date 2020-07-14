<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Backup\Admin\Model;

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Backup\Admin\Model\Mixin\GetErrorsFromExceptions;
use Akeeba\Engine\Archiver\Directftp;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use FOF30\Model\Model;
use JFile;
use JLoader;
use JText;

class Restore extends Model
{
	use GetErrorsFromExceptions;

	/** @var   array  The backup record being restored */
	private $data;

	/** @var   string  The extension of the archive being restored */
	private $extension;

	/** @var   string  Absolute path to the archive being restored */
	private $path;

	/** @var   string  Random password, used to secure the restoration */
	public $password;

	/**
	 * Generates a pseudo-random password
	 *
	 * @param   int  $length  The length of the password in characters
	 *
	 * @return  string  The requested password string
	 */
	public function makeRandomPassword($length = 32)
	{
		\JLoader::import('joomla.user.helper');

		return \JUserHelper::genRandomPassword($length);
	}

	/**
	 * Validates the data passed to the request.
	 *
	 * @return  mixed  True if all is OK, an error string if something is wrong
	 */
	public function validateRequest()
	{
		// Is this a valid backup entry?
		$ids       = $this->getIDsFromRequest();
		$id        = array_pop($ids);
		$profileID = $this->input->getInt('profileid', 0);

		// No backup IDs in the request and no backup profile (which means I should use its latest backup record) is found.
		if (empty($id) && ($profileID <= 0))
		{
			return JText::_('COM_AKEEBA_RESTORE_ERROR_INVALID_RECORD');
		}

		if (empty($id))
		{
			try
			{
				$id = $this->getLatestBackupForProfile($profileID);
			}
			catch (\RuntimeException $e)
			{
				return $e->getMessage();
			}
		}

		$data = Platform::getInstance()->get_statistics($id);

		if (empty($data))
		{
			return JText::_('COM_AKEEBA_RESTORE_ERROR_INVALID_RECORD');
		}

		if ($data['status'] != 'complete')
		{
			return JText::_('COM_AKEEBA_RESTORE_ERROR_INVALID_RECORD');
		}

		// Load the profile ID (so that we can find out the output directory)
		$profile_id = $data['profile_id'];
		Platform::getInstance()->load_configuration($profile_id);

		$path   = $data['absolute_path'];
		$exists = @file_exists($path);

		if (!$exists)
		{
			// Let's try figuring out an alternative path
			$config = Factory::getConfiguration();
			$path   = $config->get('akeeba.basic.output_directory', '') . '/' . $data['archivename'];
			$exists = @file_exists($path);
		}

		if (!$exists)
		{
			return JText::_('COM_AKEEBA_RESTORE_ERROR_ARCHIVE_MISSING');
		}

		$filename  = basename($path);
		$lastdot   = strrpos($filename, '.');
		$extension = strtoupper(substr($filename, $lastdot + 1));

		if (!in_array($extension, ['JPS', 'JPA', 'ZIP']))
		{
			return JText::_('COM_AKEEBA_RESTORE_ERROR_INVALID_TYPE');
		}

		$this->data      = $data;
		$this->path      = $path;
		$this->extension = $extension;

		return true;
	}

	/**
	 * Finds the latest backup for a given backup profile with an "OK" status (the archive file exists on your server).
	 * If none is found a RuntimeException is thrown.
	 *
	 * This method uses the code from the Transfer model for DRY reasons.
	 *
	 * @param   int  $profileID  The profile in which to locate the latest valid backup
	 *
	 * @return  int
	 *
	 * @throws  \RuntimeException
	 *
	 * @since   5.3.0
	 */
	public function getLatestBackupForProfile($profileID)
	{
		/** @var Transfer $transferModel */
		$transferModel = $this->container->factory->model('Transfer')->tmpInstance();
		$latestBackup  = $transferModel->getLatestBackupInformation($profileID);

		if (empty($latestBackup))
		{
			throw new \RuntimeException(JText::sprintf('COM_AKEEBA_RESTORE_ERROR_NO_LATEST', $profileID));
		}

		return $latestBackup['id'];
	}

	/**
	 * Creates the restoration.php file which is used to configure Akeeba Restore (restore.php). Without it, resotre.php
	 * is completely inert, preventing abuse.
	 *
	 * @return  bool
	 */
	public function createRestorationINI()
	{
		// Get a password
		$this->password = $this->makeRandomPassword(32);
		$this->setState('password', $this->password);

		// Do we have to use FTP?
		$procengine = $this->getState('procengine', 'direct');

		// Get the absolute path to site's root
		$siteroot = JPATH_SITE;

		// Get the JPS password
		$password = addslashes($this->getState('jps_key'));

		// Get min / max execution time
		$min_exec = $this->getState('min_exec', 0, 'int');
		$max_exec = $this->getState('max_exec', 5, 'int');
		$bias     = 75;

		$data = "<?php\ndefined('_AKEEBA_RESTORATION') or die();\n";
		$data .= '$restoration_setup = array(' . "\n";
		$data .= <<<ENDDATA
	'kickstart.security.password' => '{$this->password}',
	'kickstart.tuning.max_exec_time' => '{$max_exec}',
	'kickstart.tuning.run_time_bias' => '{$bias}',
	'kickstart.tuning.min_exec_time' => '{$min_exec}',
	'kickstart.procengine' => '$procengine',
	'kickstart.setup.sourcefile' => '{$this->path}',
	'kickstart.setup.destdir' => '$siteroot',
	'kickstart.setup.restoreperms' => '0',
	'kickstart.setup.filetype' => '{$this->extension}',
	'kickstart.setup.dryrun' => '0',
	'kickstart.jps.password' => '$password'
ENDDATA;

		/**
		 * Should I enable the “Delete everything before extraction” option?
		 *
		 * This requires TWO conditions to be true:
		 *
		 * 1. The application-level configuration option showDeleteOnRestore was enabled to show the option to the user
		 * 2. The user has enabled this option (the Controller sets it in the zapbefore model variable)
		 */
		$shownDeleteOnRestore = $this->container->params->get('showDeleteOnRestore', 0) == 1;

		if ($shownDeleteOnRestore && ($this->getState('zapbefore', 0, 'int') == 1))
		{
			$data .= ",\n\t'kickstart.setup.zapbefore' => '1'";
		}

		// If we're using the FTP or Hybrid engine we need to set up the FTP parameters
		if (in_array($procengine, ['ftp', 'hybrid']))
		{
			$ftp_host = $this->getState('ftp_host', '');
			$ftp_port = $this->getState('ftp_port', '21');
			$ftp_user = $this->getState('ftp_user', '');
			$ftp_pass = addcslashes($this->getState('ftp_pass', ''), "'\\");
			$ftp_root = $this->getState('ftp_root', '');
			$ftp_ssl  = $this->getState('ftp_ssl', 0);
			$ftp_pasv = $this->getState('ftp_root', 1);
			$tempdir  = $this->getState('tmp_path', '');
			$data     .= <<<ENDDATA
	,
	'kickstart.ftp.ssl' => '$ftp_ssl',
	'kickstart.ftp.passive' => '$ftp_pasv',
	'kickstart.ftp.host' => '$ftp_host',
	'kickstart.ftp.port' => '$ftp_port',
	'kickstart.ftp.user' => '$ftp_user',
	'kickstart.ftp.pass' => '$ftp_pass',
	'kickstart.ftp.dir' => '$ftp_root',
	'kickstart.ftp.tempdir' => '$tempdir'
ENDDATA;
		}

		$data .= ');';

		// Remove the old file, if it's there...
		JLoader::import('joomla.filesystem.file');
		$configpath = JPATH_COMPONENT_ADMINISTRATOR . '/restoration.php';
		clearstatcache(true, $configpath);

		if (@file_exists($configpath))
		{
			if (!@unlink($configpath))
			{
				JFile::delete($configpath);
			}
		}

		// Write new file
		$result = JFile::write($configpath, $data);

		// Clear opcode caches for the generated .php file
		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($configpath);
		}

		if (function_exists('apc_compile_file'))
		{
			apc_compile_file($configpath);
		}

		if (function_exists('wincache_refresh_if_changed'))
		{
			wincache_refresh_if_changed([$configpath]);
		}

		if (function_exists('xcache_asm'))
		{
			xcache_asm($configpath);
		}

		return $result;
	}

	/**
	 * Handles an AJAX request
	 *
	 * @return  mixed
	 */
	public function doAjax()
	{
		$ajax = $this->getState('ajax');
		switch ($ajax)
		{
			// FTP Connection test for DirectFTP
			case 'testftp':
				// Grab request parameters
				$config = [
					'host'    => $this->input->get('host', '', 'none', 2),
					'port'    => $this->input->get('port', 21, 'int'),
					'user'    => $this->input->get('user', '', 'none', 2),
					'pass'    => $this->input->get('pass', '', 'none', 2),
					'initdir' => $this->input->get('initdir', '', 'none', 2),
					'usessl'  => $this->input->get('usessl', 'cmd') == 'true',
					'passive' => $this->input->get('passive', 'cmd') == 'true',
				];

				// Perform the FTP connection test
				$test = new Directftp();

				try
				{
					$test->initialize('', $config);
				}
				catch (\Exception $e)
				{
					return implode("\n", $this->getErrorsFromExceptions($e));
				}

				return true;
				break;

			// Unrecognized AJAX task
			default:
				$result = false;
				break;
		}

		return $result;
	}

	/**
	 * Gets the list of IDs from the request data
	 *
	 * @return array
	 */
	protected function getIDsFromRequest()
	{
		// Get the ID or list of IDs from the request or the configuration
		$cid = $this->input->get('cid', [], 'array');
		$id  = $this->input->getInt('id', 0);

		if (is_array($cid) && !empty($cid))
		{
			return array_unique(array_map(function ($x) {
				return (int) $x;
			}, $cid));
		}

		if (!empty($id))
		{
			return [$id];
		}

		return [];
	}
}
