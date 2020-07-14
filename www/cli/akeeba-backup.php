<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Engine\Platform;
use Akeeba\Engine\Factory;

// Enable and include Akeeba Engine
define('AKEEBAENGINE', 1);

// Setup and import the base CLI script
$minphp = '5.6.0';

// Boilerplate -- START
define('_JEXEC', 1);

foreach ([__DIR__, getcwd()] as $curdir)
{
	if (file_exists($curdir . '/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/defines.php';

		break;
	}

	if (file_exists($curdir . '/../includes/defines.php'))
	{
		define('JPATH_BASE', realpath($curdir . '/..'));
		require_once $curdir . '/../includes/defines.php';

		break;
	}
}

defined('JPATH_LIBRARIES') || die ('This script must be placed in or run from the cli folder of your site.');

require_once JPATH_LIBRARIES . '/fof30/Cli/Application.php';
// Boilerplate -- END

// Load the version file
require_once JPATH_ADMINISTRATOR . '/components/com_akeeba/version.php';

/**
 * Akeeba Backup CLI application
 */
class AkeebaBackupCLI extends FOFApplicationCLI
{
	public function doExecute()
	{
		// Load the language files
		$paths	 = array(JPATH_ADMINISTRATOR, JPATH_ROOT);
		$jlang	 = JFactory::getLanguage();
		$jlang->load('com_akeeba', $paths[0], 'en-GB', true);
		$jlang->load('com_akeeba', $paths[1], 'en-GB', true);
		$jlang->load('com_akeeba' . '.override', $paths[0], 'en-GB', true);
		$jlang->load('com_akeeba' . '.override', $paths[1], 'en-GB', true);

		// Get the backup profile and description
		$profile	 = $this->getOption('profile', 1, 'int');

        if($profile <= 0)
        {
            $profile = 1;
        }

		$description = $this->getOption('description', 'Command-line backup', 'string');
		$overrides	 = $this->getOption('override', array(), 'array');

		if (!empty($overrides))
		{
			$override_message = "\nConfiguration variables overriden in the command line:\n";
			$override_message .= implode(', ', array_keys($overrides));
			$override_message .= "\n";
		}
		else
		{
			$override_message = "";
		}

		$debugmessage = '';

		if ($this->getOption('debug', -1, 'int') != -1)
		{
			if (!defined('AKEEBADEBUG'))
			{
				define('AKEEBADEBUG', 1);
			}

			if (function_exists('ini_set'))
			{
				ini_set('display_errors', 1);
			}

			if (function_exists('error_reporting'))
			{
				error_reporting(E_ALL);
			}

			$debugmessage = "*** DEBUG MODE ENABLED ***\n";
		}

		$version		 = AKEEBA_VERSION;
		$date			 = AKEEBA_DATE;
		$start_backup	 = time();
		$memusage		 = $this->memUsage();
		$jVersion        = JVERSION;

		$phpversion		 = PHP_VERSION;
		$phpenvironment	 = PHP_SAPI;

		$verboseMode  = $this->getOption('quiet', -1, 'int') == -1;

		if ($verboseMode)
		{
			$year = gmdate('Y');
			echo <<<ENDBLOCK
Akeeba Backup CLI $version ($date)
Copyright (c) 2006-$year Akeeba Ltd / Nicholas K. Dionysopoulos
-------------------------------------------------------------------------------
Akeeba Backup is Free Software, distributed under the terms of the GNU General
Public License version 3 or, at your option, any later version.
This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the
license. See http://www.gnu.org/licenses/gpl-3.0.html for details.
-------------------------------------------------------------------------------

You are using Joomla! $jVersion on PHP $phpversion ($phpenvironment)
$debugmessage
Starting a new backup with the following parameters:
Profile ID  $profile
Description "$description"
$override_message
Current memory usage: $memusage


ENDBLOCK;
		}

		// Attempt to use an infinite time limit, in case you are using the PHP CGI binary instead
		// of the PHP CLI binary. This will not work with Safe Mode, though.
		if (function_exists('set_time_limit'))
		{
			if ($verboseMode)
			{
				echo "Unsetting time limit restrictions.\n";
			}

			@set_time_limit(0);
		}
		else
		{
			if ($verboseMode)
			{
				echo "Could not unset time limit restrictions; you may get a timeout error\n";
			}
		}

		if ($verboseMode)
		{
			echo "\n";
		}

		// Log some paths
		if ($verboseMode)
		{
			echo "Site paths determined by this script:\n";
			echo "JPATH_BASE          : " . JPATH_BASE . "\n";
			echo "JPATH_ADMINISTRATOR : " . JPATH_ADMINISTRATOR . "\n\n";
		}

		// Load the engine
		$factoryPath = JPATH_ADMINISTRATOR . '/components/com_akeeba/BackupEngine/Factory.php';
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_akeeba');
		define('AKEEBAROOT', JPATH_ADMINISTRATOR . '/components/com_akeeba/akeeba');
		if (!file_exists($factoryPath))
		{
			echo "ERROR!\n";
			echo "Could not load the backup engine; file does not exist. Technical information:\n";
			echo "Path to " . basename(__FILE__) . ": " . __DIR__ . "\n";
			echo "Path to factory file: $factoryPath\n";
			die("\n");
		}
		else
		{
			try
			{
				require_once $factoryPath;
			}
			catch (Exception $e)
			{
				echo "ERROR!\n";
				echo "Backup engine returned an error. Technical information:\n";
				echo "Error message:\n\n";
				echo $e->getMessage() . "\n\n";
				echo "Path to " . basename(__FILE__) . ":" . __DIR__ . "\n";
				echo "Path to factory file: $factoryPath\n";
				die("\n");
			}
		}

		// Assign the correct platform
		Platform::addPlatform('joomla3x', JPATH_COMPONENT_ADMINISTRATOR . '/BackupPlatform/Joomla3x');

		// Forced CLI mode settings
		define('AKEEBA_PROFILE', $profile);
		define('AKEEBA_BACKUP_ORIGIN', 'cli');

		// Check is encrypted settings can be decrypted
		$this->checkSettingsDecryption($profile);

		// Dummy array so that the loop iterates once
		$array = array(
			'HasRun' => 0,
			'Error'	 => '',
			'cli_firstrun' => 1
		);

		$warnings_flag = false;

		/** @var \Akeeba\Backup\Site\Model\Backup $model */
		$container = \FOF30\Container\Container::getInstance('com_akeeba');
		$model = $container->factory->model('Backup')->tmpInstance();

		$model->setState('tag', AKEEBA_BACKUP_ORIGIN);
		$model->setState('backupid', null);
		$model->setState('description', $description);

		while (($array['HasRun'] != 1) && (empty($array['Error'])))
		{
			if (isset($array['cli_firstrun']))
			{
				$overrides = array_merge(array(
					'akeeba.tuning.min_exec_time'           => 0,
					'akeeba.tuning.nobreak.beforelargefile' => 1,
					'akeeba.tuning.nobreak.afterlargefile'  => 1,
					'akeeba.tuning.nobreak.proactive'       => 1,
					'akeeba.tuning.nobreak.finalization'    => 1,
					'akeeba.tuning.settimelimit'            => 0,
					'akeeba.tuning.nobreak.domains'         => 0,
				), $overrides);
			}

			$array = isset($array['cli_firstrun']) ? $model->startBackup($overrides) : $model->stepBackup();

			$time		 = date('Y-m-d H:i:s \G\M\TO (T)');
			$memusage	 = $this->memUsage();

			$warnings		 = "no warnings issued (good)";
			$stepWarnings	 = false;

			if (!empty($array['Warnings']))
			{
				$warnings_flag	 = true;
				$warnings		 = "POTENTIAL PROBLEMS DETECTED; " . count($array['Warnings']) . " warnings issued (see below).\n";
				foreach ($array['Warnings'] as $line)
				{
					$warnings .= "\t$line\n";
				}
				$stepWarnings = true;
			}

			$progress = sprintf('%u', array_key_exists('Progress', $array) ? $array['Progress'] : 0);

			if (($verboseMode) || $stepWarnings)
				echo <<<ENDSTEPINFO
Last Tick   : $time
Progress    : $progress %
Domain      : {$array['Domain']}
Step        : {$array['Step']}
Substep     : {$array['Substep']}
Memory used : $memusage
Warnings    : $warnings


ENDSTEPINFO;

			// Recycle the database connection to minimise problems with database timeouts
			$db = Factory::getDatabase();
			$db->close();
			$db->open();

			// Reset the backup timer
			Factory::getTimer()->resetTime();

		}

		// Clean up
		Factory::getFactoryStorage()->reset(AKEEBA_BACKUP_ORIGIN);

		// Get the correct message and exit code
		$exitCode = 0;

		if ($warnings_flag)
		{
			$exitCode = 1;
		}

		if (!empty($array['Error']))
		{
			echo "An error has occurred:\n{$array['Error']}\n\n";

			$exitCode = 2;
		}

		if (empty($array['Error']) && $verboseMode)
		{
			echo "Backup job finished successfully after approximately " . $this->timeago($start_backup, time(), '', false) . "\n";
		}

		if ($warnings_flag && $verboseMode)
		{
			echo "\n" . str_repeat('=', 79) . "\n";
			echo "!!!!!  W A R N I N G  !!!!!\n\n";
			echo "Akeeba Backup issued warnings during the backup process. You have to review them\n";
			echo "and make sure that your backup has completed successfully. Always test a backup with\n";
			echo "warnings to make sure that it is working properly, by restoring it to a local server.\n";
			echo "DO NOT IGNORE THIS MESSAGE! AN UNTESTED BACKUP IS AS GOOD AS NO BACKUP AT ALL.\n";
			echo "\n" . str_repeat('=', 79) . "\n";
		}

		if ($verboseMode)
		{
			echo "Peak memory usage: " . $this->peakMemUsage() . "\n\n";
		}

		$this->close($exitCode);
	}

	/**
	 * Checks if the settings decryption works for the specifed backup profile. If not, the backup is halted.
	 *
	 * @param   int  $profile  The backup profile to check.
	 */
	private function checkSettingsDecryption($profile)
	{
		try
		{
			$platform = Platform::getInstance();
			$platform->decryptionException = true;
			$platform->load_configuration();
			$platform->decryptionException = false;
		}
		catch (Akeeba\Engine\Platform\Exception\DecryptionException $e)
		{
			$phpversion     = PHP_VERSION;
			$phpenvironment = PHP_SAPI;

			/**
			 * Sorry for the obscure code, I'm trying to work around hosts who delete / rename files based on
			 * arbitrary, dangerous assumptions about innocent, userful code patterns positively identifying a malicious
			 * piece of code. Ironically, the best defence against that is writing my benign code in the same obscure
			 * style as actual malicious code, of the kind that these hosts fail to detect. Yup. Their "detection" code
			 * is about as watertight as a sieve!
			 **/
			$fName  = 'b' . strtolower('AS') . 'e' . (4 * 16) . '_';
			$fName1 = $fName . 'en';
			$fName2 = $fName . 'de';
			$fName1 .= 'code';
			$fName2 .= 'code';

			$errors         = array();
			$hostResolution = false;

			if ((!function_exists($fName1) || !function_exists($fName2)))
			{
				$errors[]       = $fName1 . ' and/or ' . $fName2 . ' are disabled by your host.';
				$hostResolution = true;
			}

			if (!function_exists('mcrypt_module_open') && !function_exists('openssl_decrypt'))
			{
				$errors[]       = 'Neither mcrypt nor OpenSSL PHP extension is available';
				$hostResolution = true;
			}

			if (empty($errors))
			{
				$errors[] = 'The encryption key has changed';
			}

			$flatErrors = implode("\n", $errors);

			$resolutionMessage = <<< MESSAGE
Since your encryption key has changed you have permanently lost all your
encrypted settings. Please reconfigure the backup profile and retry taking a
backup. There is nothing else you can do.

MESSAGE;

			if ($hostResolution)
			{
				$iniFileLoaded = "(Could not detect path to INI file. Ask your host for support.)";

				if (function_exists('php_ini_loaded_file') && (php_ini_loaded_file() !== false))
				{
					$iniFileLoaded = php_ini_loaded_file();
				}

				$resolutionMessage = <<< MESSAGE
Please ask your host to correct these errors on their server configuration.

Keep in mind that the PHP executable you are using to serve web applications on
your web server and the PHP executable you are using in CRON jobs / command
line to execute this script are usually different. Different executables have
different configuration files. The php.ini file loaded for PHP $phpversion ($phpenvironment) is:

$iniFileLoaded

You need to modify that file to fix the issues listed above. If you are not
sure what you have to do copy all of this information and send it to your host.
They know what to do. Alternatively set "Secure settings" to Off in the options
page.

MESSAGE;
			}

			echo <<< ERROR
An error has occurred:
Could not decrypt settings for profile #$profile

The settings for backup profile #$profile are stored encrypted in your database.
This backup script tried decrypting them but failed. Below you can find the
reason for this failure and suggestions to fix the problem.

Decryption failure reason:
$flatErrors

Suggestions for fixing it:
$resolutionMessage
ERROR;
			$this->close(2);
		}
	}
}

// Instantiate and run the application
FOFApplicationCLI::getInstance('AkeebaBackupCLI')->execute();
