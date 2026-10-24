<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') or die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\TriggerEventTrait;
use Akeeba\Engine\Base\Part;
use Akeeba\Engine\Core\Timer;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Psr\Log\LogLevel;
use Akeeba\Engine\Util\PushMessages;
use Akeeba\WebPush\WebPush\WebPush;
use DateTimeZone;
use DirectoryIterator;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\User\User;
use RuntimeException;

#[\AllowDynamicProperties]
class BackupModel extends BaseDatabaseModel
{
	use TriggerEventTrait;

	/**
	 * Convert the old, plaintext log files (.log) into their .log.php counterparts.
	 *
	 * @param   int  $timeOut  Maximum time, in seconds, to spend doing this conversion.
	 *
	 * @return  void
	 *
	 * @since   7.0.3
	 */
	public function convertLogFiles($timeOut = 10)
	{
		$registry = Factory::getConfiguration();
		$logDir   = $registry->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]', true);

		$timer = new Timer($timeOut, 75);

		// Part I. Remove these obsolete files first
		$killFiles = [
			'akeeba.log',
			'akeeba.backend.log',
			'akeeba.frontend.log',
			'akeeba.cli.log',
			'akeeba.json.log',
		];

		foreach ($killFiles as $fileName)
		{
			$path = $logDir . '/' . $fileName;

			if (@is_file($path))
			{
				@unlink($path);
			}
		}

		if ($timer->getTimeLeft() <= 0.01)
		{
			return;
		}

		// Part II. Convert .log files.
		try
		{
			$di = new DirectoryIterator($logDir);
		}
		catch (Exception $e)
		{
			return;
		}

		foreach ($di as $file)
		{

			try
			{
				if (!$file->isFile())
				{
					continue;
				}

				$baseName = $file->getFilename();

				if (substr($baseName, 0, 7) !== 'akeeba.')
				{
					continue;
				}

				if (substr($baseName, -4) !== '.log')
				{
					continue;
				}

				$this->convertLogFile($file->getPathname());

				if ($timer->getTimeLeft() <= 0.01)
				{
					return;
				}
			}
			catch (Exception $e)
			{
				/**
				 * Someone did something stupid, like using the site's root as the backup output directory while having
				 * an open_basedir restriction. Sorry, mate, you get insecure junk. We had warned you. You didn't heed
				 * the warning. That's your problem now.
				 */
			}
		}
	}

	/**
	 * Get the default backup description.
	 *
	 * The default description is "Backup taken on DATE TIME" where DATE TIME is the current timestamp in the most
	 * specific timezone. The timezone order, from least to most specific, is:
	 * * UTC (fallback)
	 * * Server Timezone from Joomla's Global Configuration
	 * * Timezone from the current user's profile (only applicable to backend backups)
	 * * Forced backup timezone
	 *
	 * @param   string  $format  Date and time format. Default: DATE_FORMAT_LC2 plus the abbreviated timezone
	 *
	 * @return  string
	 */
	public function getDefaultDescription(string $format = ''): string
	{
		// If no date format is specified we use DATE_FORMAT_LC2 plus the abbreviated timezone
		if (empty($format))
		{
			$format = Text::_('DATE_FORMAT_LC2') . ' T';
		}

		// Get the most specific Joomla timezone (UTC, overridden by server timezone, overridden by user timezone)
		$joomlaTimezone = JoomlaFactory::getApplication()->get('offset', 'UTC');

		if (!JoomlaFactory::getApplication()->isClient('cli'))
		{
			$user = JoomlaFactory::getApplication()->getIdentity() ?? (new User());

			if (!$user->guest)
			{
				$joomlaTimezone = $user->getParam('timezone', $joomlaTimezone);
			}
		}

		$timezone = $joomlaTimezone;

		// The forced timezone overrides everything else
		$forcedTZ = Platform::getInstance()->get_platform_configuration_option('forced_backup_timezone', 'AKEEBA/DEFAULT');

		if (!empty($forcedTZ) && ($forcedTZ != 'AKEEBA/DEFAULT'))
		{
			$timezone = $forcedTZ;
		}

		// Convert the current date and time to the selected timezone
		$dateNow = clone JoomlaFactory::getDate();
		$tz      = new DateTimeZone($timezone);

		$dateNow->setTimezone($tz);

		return Text::_('COM_AKEEBABACKUP_BACKUP_DEFAULT_DESCRIPTION') . ' ' . $dateNow->format($format, true);
	}

	/**
	 * Get the profile used to take the last backup for the specified tag
	 *
	 * @param   string       $tag       The backup tag a.k.a. backup origin (backend, frontend, json, ...)
	 * @param   string|null  $backupId  (optional) The Backup ID
	 *
	 * @return  int  The profile ID of the latest backup taken with the specified tag / backup ID
	 */
	public function getLastBackupProfile(string $tag, ?string $backupId = null): int
	{
		$filters = [
			['field' => 'tag', 'value' => $tag],
		];

		if (!empty($backupId))
		{
			$filters[] = ['field' => 'backupid', 'value' => $backupId];
		}

		$statList = Platform::getInstance()->get_statistics_list([
				'filters' => $filters,
				'order'   => [
					'by' => 'id', 'order' => 'DESC',
				],
			]
		);

		if (is_array($statList))
		{
			$stat = array_pop($statList);

			return (int) $stat['profile_id'];
		}

		// Backup entry not found. If backupId was specified, try without a backup ID
		if (!empty($backupId))
		{
			return $this->getLastBackupProfile($tag);
		}

		// Else, return the default backup profile
		return 1;
	}

	/**
	 * Send a push notification for a failed backup
	 *
	 * State variables expected (MUST be set):
	 * errorMessage  The error message
	 *
	 * @return  void
	 */
	public function pushFail()
	{
		$this->initialiseWebPush();

		$errorMessage = $this->getState('errorMessage');

		$platform = Platform::getInstance();
		$key      = 'COM_AKEEBABACKUP_PUSH_ENDBACKUP_FAIL_BODY_WITH_MESSAGE';

		if (empty($errorMessage))
		{
			$key = 'COM_AKEEBABACKUP_PUSH_ENDBACKUP_FAIL_BODY';
		}

		$pushSubject = sprintf(
			$platform->translate('COM_AKEEBABACKUP_PUSH_ENDBACKUP_FAIL_SUBJECT'),
			$platform->get_site_name(),
			$platform->get_host()
		);
		$pushDetails = sprintf(
			$platform->translate($key),
			$platform->get_site_name(),
			$platform->get_host(),
			$errorMessage
		);

		$push = new PushMessages();
		$push->message($pushSubject, $pushDetails);
	}

	/**
	 * Starts or step a backup process. Set the state variable "ajax" to the task you want to execute OR call the
	 * relevant public method directly.
	 *
	 * @return  array  An Akeeba Engine return array
	 * @throws  Exception
	 *
	 * @noinspection PhpUnused
	 */
	public function runBackup(): array
	{
		$this->initialiseWebPush();

		if (!defined('AKEEBADEBUG') && JoomlaFactory::getApplication()->get('debug', false))
		{
			define('AKEEBADEBUG', 1);
		}

		$ret_array = [];

		$ajaxTask = $this->getState('ajax');

		switch ($ajaxTask)
		{
			// Start a new backup
			case 'start':
				$ret_array = $this->startBackup();
				break;

			// Step through a backup
			case 'step':
				$ret_array = $this->stepBackup();
				break;

			// Send a push notification for backup failure
			case 'pushFail':
				$this->pushFail();
				break;

			default:
				break;
		}

		return $ret_array;
	}

	/**
	 * Starts a new backup.
	 *
	 * State variables expected
	 *
	 * backupid     The ID of the backup. If none is set up we will create a new one in the form id123
	 * tag          The backup tag, e.g. "frontend". If none is set up we'll get it through the Platform.
	 * description  The description of the backup (optional)
	 * comment      The comment of the backup (optional)
	 * jpskey       JPS password
	 * angiekey     ANGIE password
	 *
	 * @param   array  $overrides  Configuration overrides
	 *
	 * @return  array  An Akeeba Engine return array
	 * @throws Exception
	 */
	public function startBackup(array $overrides = []): array
	{
		$this->initialiseWebPush();

		// Get information from the model state
		$tag         = $this->getState('tag', null);
		$description = $this->getState('description', '');
		$comment     = $this->getState('comment', '');
		$jpskey      = $this->getState('jpskey', null);
		$angiekey    = $this->getState('angiekey', null);
		$backupId    = $this->getBackupId();

		$profile = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.profile', defined('AKEEBA_PROFILE') ? AKEEBA_PROFILE : 1);

		// Use the default description if none specified
		$description = $description ?: $this->getDefaultDescription();

		// Try resetting the engine
		try
		{
			Factory::resetState([
				'maxrun' => 0,
			]);
		}
		catch (Exception $e)
		{
			// This will die if the output directory is invalid. Let it die, then.
		}

		// Remove any stale memory files left over from the previous step
		if (empty($tag))
		{
			$tag = Platform::getInstance()->get_backup_origin();
		}

		$tempVarsTag = $tag;
		$tempVarsTag .= empty($backupId) ? '' : ('.' . $backupId);

		Factory::getFactoryStorage()->reset($tempVarsTag);
		Factory::nuke();
		Factory::getLog()->log(LogLevel::DEBUG, " -- Resetting Akeeba Engine factory ($tag.$backupId)");
		Platform::getInstance()->load_configuration();

		// Autofix the output directory
		/** @var ConfigurationwizardModel $confWizModel */
		$confWizModel = $this->getMVCFactory()->createModel('Configurationwizard', 'Administrator');
		$confWizModel->autofixDirectories();

		// Rebase Off-site Folder Inclusion filters to use site path variables
		/** @var IncludefoldersModel $incFoldersModel */
		$incFoldersModel = $this->getMVCFactory()->createModel('Includefolders', 'Administrator');

		if (is_object($incFoldersModel) && method_exists($incFoldersModel, 'rebaseFiltersToSiteDirs'))
		{
			$incFoldersModel->rebaseFiltersToSiteDirs();
		}

		// Should I apply any configuration overrides?
		if (is_array($overrides) && !empty($overrides))
		{
			$config        = Factory::getConfiguration();
			$protectedKeys = $config->getProtectedKeys();
			$config->resetProtectedKeys();

			foreach ($overrides as $k => $v)
			{
				$config->set($k, $v);
			}

			$config->setProtectedKeys($protectedKeys);
		}

		// Check if there are critical issues preventing the backup
		if (!Factory::getConfigurationChecks()->getShortStatus())
		{
			$configChecks = Factory::getConfigurationChecks()->getDetailedStatus();

			foreach ($configChecks as $checkItem)
			{
				if ($checkItem['severity'] != 'critical')
				{
					continue;
				}

				return [
					'HasRun'   => 0,
					'Domain'   => 'init',
					'Step'     => '',
					'Substep'  => '',
					'Error'    => 'Failed configuration check Q' . $checkItem['code'] . ': ' . $checkItem['description'] . '. Please refer to https://www.akeeba.com/documentation/warnings/q' . $checkItem['code'] . '.html for more information and troubleshooting instructions.',
					'Warnings' => [],
					'Progress' => 0,
				];
			}
		}

		// Set up Kettenrad
		$options = [
			'description' => $description,
			'comment'     => $comment,
			'jpskey'      => $jpskey,
			'angiekey'    => $angiekey,
		];

		if (is_null($jpskey))
		{
			unset ($options['jpskey']);
		}

		if (is_null($angiekey))
		{
			unset ($options['angiekey']);
		}

		$kettenrad = Factory::getKettenrad();
		$kettenrad->setBackupId($backupId);
		$kettenrad->setup($options);

		$this->setState('backupid', $backupId);

		/**
		 * Convert log files in the backup output directory
		 *
		 * This removes the obsolete, default log files (akeeba.(backend|frontend|cli|json).log and converts the old .log
		 * files into their .php counterparts.
		 *
		 * We are doing this when loading the the Control Panel page but ALSO when taking a new backup because some
		 * people might be installing updates and taking backups automatically, without visiting the Control Panel
		 * except in rare cases.
		 */
		$this->convertLogFiles(3);

		/**
		 * We need to run tick() twice in the first backup step.
		 *
		 * The first tick() will reset the backup engine and start a new backup. However, no backup record is created
		 * at this point. This means that Factory::loadState() cannot find a backup record, therefore it cannot read
		 * the backup profile being used, therefore it will assume it's profile #1.
		 *
		 * The second tick() creates the backup record without doing much else, fixing this issue.
		 *
		 * However, if you have conservative settings where the min exec time is MORE than the max exec time the second
		 * tick would never run. Therefore we need to tell the first tick to ignore the time settings (since it only
		 * takes a few milliseconds to execute anyway) and then apply the time settings on the second tick (which also
		 * only takes a few milliseconds). This is why we have setIgnoreMinimumExecutionTime before and after the first
		 * tick. DO NOT REMOVE THESE.
		 *
		 * Furthermore, if the first tick reaches the end of backup or an error condition we MUST NOT run the second
		 * tick() since the engine state will be invalid. Hence the check for the state that performs a hard break. This
		 * could happen if you have a sufficiently high max execution time, no break between steps and we fail to
		 * execute any step, e.g. the installer image is missing, a database error occurred or we can not list the files
		 * and directories to back up.
		 *
		 * THEREFORE, DO NOT REMOVE THE LOOP OR THE if-BLOCK IN IT, THEY ARE THERE FOR A GOOD REASON!
		 */
		$kettenrad->setIgnoreMinimumExecutionTime(true);

		for ($i = 0; $i < 2; $i++)
		{
			$kettenrad->tick();

			if (in_array($kettenrad->getState(), [Part::STATE_FINISHED, Part::STATE_ERROR]))
			{
				break;
			}

			$kettenrad->setIgnoreMinimumExecutionTime(false);
		}

		$ret_array = $kettenrad->getStatusArray();

		// Notify the actionlog plugin
		$statistics = Factory::getStatistics();
		$this->triggerEvent('onStart', [$statistics->getId(), $profile]);

		try
		{
			Factory::saveState($tag, $backupId);
		}
		catch (RuntimeException $e)
		{
			$ret_array['Error'] = $e->getMessage();
		}

		return $ret_array;
	}

	/**
	 * Steps through a backup.
	 *
	 * State variables expected (MUST be set):
	 * backupid     The ID of the backup.
	 * tag          The backup tag, e.g. "frontend".
	 * profile      (optional) The profile ID of the backup.
	 *
	 * @param   bool  $requireBackupId  Should the backup ID be required?
	 *
	 * @return  array  An Akeeba Engine return array
	 * @throws Exception
	 */
	public function stepBackup($requireBackupId = true)
	{
		$this->initialiseWebPush();

		// Get information from the model state
		$tag      = $this->getState('tag', defined('AKEEBA_BACKUP_ORIGIN') ? AKEEBA_BACKUP_ORIGIN : null);
		$backupId = $this->getState('backupid', null);

		// populateState() pushes the current profile number into the state.
		$profile = max(0, (int) $this->getState('profile', 0)) ?: $this->getLastBackupProfile($tag, $backupId);

		// Set the active profile
		JoomlaFactory::getApplication()->getSession()->set('akeebabackup.profile', $profile);

		if (!defined('AKEEBA_PROFILE'))
		{
			define('AKEEBA_PROFILE', $profile);
		}

		// Run a backup step
		$ret_array = [
			'HasRun'   => 0,
			'Domain'   => 'init',
			'Step'     => '',
			'Substep'  => '',
			'Error'    => '',
			'Warnings' => [],
			'Progress' => 0,
		];

		try
		{
			// Reload the configuration
			Platform::getInstance()->load_configuration($profile);

			// Load the engine from storage
			Factory::loadState($tag, $backupId, $requireBackupId);

			// Set the backup ID and run a backup step
			$kettenrad = Factory::getKettenrad();
			$kettenrad->tick();
			$ret_array = $kettenrad->getStatusArray();
		}
		catch (Exception $e)
		{
			$ret_array['Error'] = $e->getMessage();
		}

		try
		{
			if (empty($ret_array['Error']) && ($ret_array['HasRun'] != 1))
			{
				Factory::saveState($tag, $backupId);
			}
		}
		catch (RuntimeException $e)
		{
			$ret_array['Error'] = $e->getMessage();
		}

		if (!empty($ret_array['Error']) || ($ret_array['HasRun'] == 1))
		{
			/**
			 * Do not nuke the Factory if we're trying to resume after an error.
			 *
			 * When the resume after error (retry) feature is enabled AND we are performing a backend backup we MUST
			 * leave the factory storage intact so we can actually resume the backup. If we were to nuke the Factory
			 * the resume would report that it cannot load the saved factory and lead to a failed backup.
			 */
			$config = Factory::getConfiguration();

			if (JoomlaFactory::getApplication()->isClient('administrator') && $config->get('akeeba.advanced.autoresume', 1))
			{
				// We are about to resume; abort.
				return $ret_array;
			}

			// Clean up
			Factory::nuke();

			$tempVarsTag = $tag;
			$tempVarsTag .= empty($backupId) ? '' : ('.' . $backupId);

			Factory::getFactoryStorage()->reset($tempVarsTag);
		}

		return $ret_array;
	}

	/**
	 * Converts a log file from .log to .log.php or, if that is not possible, to .php
	 *
	 * @param   string  $filePath
	 *
	 * @return  void
	 *
	 * @since   7.0.3
	 */
	protected function convertLogFile(string $filePath): void
	{
		/**
		 * The name of the converted log file is the same with the extension .php appended to it. Some hosts, like WP
		 * Engine, do not let us write to files with a .php extension. In this case we try a .php extension replacing
		 * the .log one. Either way the converted log file is not readable over the web.
		 */
		$candidates = [
			$filePath . '.php',
			substr($filePath, 0, -4) . '.php',
		];

		foreach ($candidates as $newFile)
		{
			// If the new log file exists I should return immediately
			if (@file_exists($newFile))
			{
				return;
			}

			if ($this->copyLogFile($filePath, $newFile))
			{
				// Delete the original (.log) file
				@unlink($filePath);

				return;
			}
		}
	}

	/**
	 * Copies a plaintext log file into a new, web inaccessible log file, prefixed with a PHP die statement.
	 *
	 * @param   string  $filePath  The .log file to copy from
	 * @param   string  $newFile   The log file to create
	 *
	 * @return  bool  True if the log file was converted in its entirety
	 *
	 * @since   10.4.0
	 */
	private function copyLogFile(string $filePath, string $newFile): bool
	{
		// Try to open the converted log file
		$fp = @fopen($newFile, 'w');

		if ($fp === false)
		{
			return false;
		}

		// Try to open the source log file (.log)
		$sourceFP = @fopen($filePath, 'r');

		if ($sourceFP === false)
		{
			@fclose($fp);
			@unlink($newFile);

			return false;
		}

		// Write the die statement to the converted log file
		$success = @fwrite($fp, '<' . '?' . 'php die(); ' . '?' . ">\n") !== false;

		// Copy data, 512KB at a time
		while ($success && !feof($sourceFP))
		{
			$chunk = @fread($sourceFP, 524288);

			if ($chunk === false)
			{
				$success = false;

				break;
			}

			$success = @fwrite($fp, $chunk) !== false;
		}

		// Close both files
		@fclose($sourceFP);
		@fclose($fp);

		// Do not leave a partially written log file behind
		if (!$success)
		{
			@unlink($newFile);
		}

		return $success;
	}

	/**
	 * Method to auto-populate the state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the
	 * configuration flag to ignore the request is set.
	 *
	 * @return  void
	 *
	 * @note    Calling getState in this method will result in recursion.
	 * @throws  Exception
	 * @since   9.0.0
	 */
	protected function populateState()
	{
		/** @var CMSApplication $app */
		$app   = JoomlaFactory::getApplication();
		$input = $app->getInput();

		$profile = (int) $app->getSession()->get('akeebabackup.profile', 1);
		$profile = defined('AKEEBA_PROFILE') ? AKEEBA_PROFILE : $profile;
		$profile = max($profile, 1);

		$stateVariables = [
			'tag'          => $input->get('tag', null, 'string'),
			'backupId'     => $input->get('backupid', null, 'string'),
			'description'  => $input->get('description', '', 'string'),
			'comment'      => $input->get('comment', '', 'html'),
			'jpskey'       => $input->get('jpskey', null, 'raw'),
			'angiekey'     => $input->get('angiekey', null, 'raw'),
			'profile'      => $input->get('profile', $profile, 'int'),
			'ajax'         => $input->get('ajax', '', 'cmd'),
			'errorMessage' => $input->get('errorMessage', '', 'raw'),
		];

		foreach ($stateVariables as $k => $v)
		{
			$this->setState($k, $v);
		}
	}

	/**
	 * Get a new backup ID string.
	 *
	 * In the past we were trying to get the next backup record ID using two methods:
	 * - Querying the information_schema.tables metadata table. In many cases we saw this returning the wrong value,
	 *   even though the MySQL documentation said this should return the next autonumber (WTF?)
	 * - Doing a MAX(id) on the table and adding 1. This didn't work correctly if the latest records were deleted by the
	 *   user.
	 *
	 * However, the backup ID does not need to be the same as the backup record ID. It only needs to be *unique*. So
	 * this time around we are using a simple, unique ID based on the current GMT date and time.
	 *
	 * @return  string
	 */
	private function getBackupId(): string
	{
		$microtime    = explode(' ', microtime(false));
		$microseconds = (int) ($microtime[0] * 1000000);

		return 'id-' . gmdate('Ymd-His') . '-' . $microseconds;
	}

	/**
	 * Make sure we can load the Web Push helper, if needed and not already loaded
	 *
	 * @return  void
	 * @since   9.3.1
	 */
	private function initialiseWebPush()
	{
		$pushPreference = Platform::getInstance()->get_platform_configuration_option('push_preference', '0');

		if ($pushPreference !== 'webpush')
		{
			return;
		}

		if (!class_exists(WebPush::class))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebabackup/vendor/autoload.php';
		}

	}
}