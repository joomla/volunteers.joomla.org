<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\GetErrorsFromExceptionsTrait;
use Akeeba\Engine\Archiver\Directftp;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryServiceInterface;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\CMS\User\UserHelper;
use RuntimeException;

#[\AllowDynamicProperties]
class RestoreModel extends BaseModel
{
	use GetErrorsFromExceptionsTrait;
	use MVCFactoryAwareTrait;

	/** @var   string  Random password, used to secure the restoration */
	public $password;

	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $option = null;

	/** @var   array  The backup record being restored */
	private $data;

	/** @var   string  The extension of the archive being restored */
	private $extension;

	/** @var   string  Absolute path to the archive being restored */
	private $path;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		parent::__construct($config);

		// Guess the option from the class name (Option)Model(View).
		if (empty($this->option))
		{
			$r = null;

			if (!preg_match('/(.*)Model/i', \get_class($this), $r))
			{
				throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_GET_NAME', __METHOD__), 500);
			}

			$this->option = ComponentHelper::getComponentName($this, $r[1]);
		}

		if ($factory)
		{
			$this->setMVCFactory($factory);

			return;
		}

		$component = JoomlaFactory::getApplication()->bootComponent($this->option);

		if ($component instanceof MVCFactoryServiceInterface)
		{
			$this->setMVCFactory($component->getMVCFactory());
		}
	}

	/**
	 * Generates a pseudo-random password
	 *
	 * @param   int  $length  The length of the password in characters
	 *
	 * @return  string  The requested password string
	 */
	public function makeRandomPassword($length = 32)
	{
		return UserHelper::genRandomPassword($length);
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
		$profileID = JoomlaFactory::getApplication()->getInput()->getInt('profileid', 0);

		// No backup IDs in the request and no backup profile (which means I should use its latest backup record) is found.
		if (empty($id) && ($profileID <= 0))
		{
			return Text::_('COM_AKEEBABACKUP_RESTORE_ERROR_INVALID_RECORD');
		}

		if (empty($id))
		{
			try
			{
				$id = $this->getLatestBackupForProfile($profileID);
			}
			catch (RuntimeException $e)
			{
				return $e->getMessage();
			}
		}

		$this->setState('id', $id);

		$data = Platform::getInstance()->get_statistics($id);

		if (empty($data))
		{
			return Text::_('COM_AKEEBABACKUP_RESTORE_ERROR_INVALID_RECORD');
		}

		if ($data['status'] != 'complete')
		{
			return Text::_('COM_AKEEBABACKUP_RESTORE_ERROR_INVALID_RECORD');
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
			return Text::_('COM_AKEEBABACKUP_RESTORE_ERROR_ARCHIVE_MISSING');
		}

		$filename  = basename($path);
		$lastdot   = strrpos($filename, '.');
		$extension = strtoupper(substr($filename, $lastdot + 1));

		if (!in_array($extension, ['JPS', 'JPA', 'ZIP']))
		{
			return Text::_('COM_AKEEBABACKUP_RESTORE_ERROR_INVALID_TYPE');
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
	 * @throws  RuntimeException|Exception
	 *
	 * @since   5.3.0
	 */
	public function getLatestBackupForProfile(int $profileID): int
	{
		/** @var TransferModel $transferModel */
		$transferModel = $this->getMVCFactory()->createModel('Transfer', 'Administrator');
		$latestBackup  = $transferModel->getLatestBackupInformation($profileID);

		if (empty($latestBackup))
		{
			throw new RuntimeException(Text::sprintf('COM_AKEEBABACKUP_RESTORE_ERROR_NO_LATEST', $profileID));
		}

		return $latestBackup['id'];
	}

	/**
	 * Creates the restoration.php file which is used to configure Akeeba Restore (restore.php). Without it, resotre.php
	 * is completely inert, preventing abuse.
	 *
	 * @return  bool
	 */
	public function createRestorationINI(): bool
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
		$min_exec = (int) $this->getState('min_exec', 0);
		$max_exec = (int) $this->getState('max_exec', 5);
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
		 * Tell the restoration script to enable stealth mode. This will have two side effects:
		 *
		 * 1. Regular visitors won't be redirected to the installation folder, potentially causing issues
		 * 2. If direct access to the installation folder has been blocked (ie by the Htaccess Maker) it will be allowed
		 */
		if ((int) $this->getState('stealthmode', 0) == 1)
		{
			$data .= ",\n\t	'kickstart.stealth.enable' => '1',\n\t'kickstart.stealth.url' => 'installation/offline.html'";
		}

		/**
		 * Should I enable the “Delete everything before extraction” option?
		 *
		 * This requires TWO conditions to be true:
		 *
		 * 1. The application-level configuration option showDeleteOnRestore was enabled to show the option to the user
		 * 2. The user has enabled this option (the Controller sets it in the zapbefore model variable)
		 */
		$cParams              = ComponentHelper::getParams($this->option);
		$shownDeleteOnRestore = $cParams->get('showDeleteOnRestore', 0) == 1;

		if ($shownDeleteOnRestore && ((int) $this->getState('zapbefore', 0) == 1))
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
		$configPath = JPATH_ADMINISTRATOR . '/components/' . $this->option . '/restoration.php';

		clearstatcache(true, $configPath);

		if (@file_exists($configPath))
		{
			if (!@unlink($configPath))
			{
				// File::delete($configPath);
			}
		}

		// Write new file
		$result = @file_put_contents($configPath, $data);

		if ($result === false)
		{
			// $result = File::write($configPath, $data);
		}

		// Clear opcode caches for the generated .php file
		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($configPath, true);
		}

		if (function_exists('apc_compile_file'))
		{
			apc_compile_file($configPath);
		}

		if (function_exists('wincache_refresh_if_changed'))
		{
			wincache_refresh_if_changed([$configPath]);
		}

		if (function_exists('xcache_asm'))
		{
			xcache_asm($configPath);
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
		$ajax  = $this->getState('ajax');
		$input = JoomlaFactory::getApplication()->getInput();

		switch ($ajax)
		{
			// FTP Connection test for DirectFTP
			case 'testftp':
				// Grab request parameters
				$config = [
					'host'    => $input->get('host', '', 'raw'),
					'port'    => $input->get('port', 21, 'int'),
					'user'    => $input->get('user', '', 'raw'),
					'pass'    => $input->get('pass', '', 'raw'),
					'initdir' => $input->get('initdir', '', 'raw'),
					'usessl'  => $input->get('usessl', 'cmd') == 'true',
					'passive' => $input->get('passive', 'cmd') == 'true',
				];

				// Perform the FTP connection test
				$test = new Directftp();

				try
				{
					$test->initialize('', $config);
				}
				catch (Exception $e)
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
		$input = JoomlaFactory::getApplication()->getInput();
		$cid   = $input->get('cid', [], 'array');
		$id    = $input->getInt('id', 0);

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