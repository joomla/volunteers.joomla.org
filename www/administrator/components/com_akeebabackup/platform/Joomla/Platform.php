<?php

/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Platform;

// Protection against direct access
defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Helper\JoomlaPublicFolder;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Akeeba\Engine\Platform\Base as BasePlatform;
use Akeeba\Engine\Psr\Log\LogLevel;
use DateTimeZone;
use Exception;
use JLoader;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;
use Joomla\Session\Session;

/**
 * Joomla! 4 platform class
 *
 * @noinspection PhpUnused
 */
class Joomla extends BasePlatform
{
	/**
	 * Override profile ID, for use in automated testing only
	 *
	 * @var   int|null
	 */
	public static $profile_id = null;

	/**
	 * Platform class priority
	 *
	 * @var  int
	 */
	public $priority = 50;

	/**
	 * This platform's name
	 *
	 * @var  string
	 */
	public $platformName = 'joomla';

	/**
	 * The database driver object to use
	 *
	 * @var   DatabaseInterface
	 * @since 9.3.0
	 */
	protected static $dbDriver = null;

	/**
	 * Flash variables for the CLI application. We use this array since we're hell bent on NOT using Joomla's broken
	 * session package.
	 *
	 * @var   array
	 *
	 * @since 5.3.5
	 */
	protected $flashVariables = [];

	/**
	 * Public constructor
	 */
	function __construct()
	{
		// New tables
		$this->tableNameProfiles = '#__akeebabackup_profiles';
		$this->tableNameStats    = '#__akeebabackup_backups';
	}

	/** @noinspection PhpUnused */
	public static function quirk_013()
	{
		$stock_dirs  = Platform::getInstance()->get_stock_directories();
		$default_out = @realpath($stock_dirs['[DEFAULT_OUTPUT]']);

		$registry = Factory::getConfiguration();
		$outdir   = $registry->get('akeeba.basic.output_directory');

		foreach ($stock_dirs as $macro => $replacement)
		{
			$outdir = str_replace($macro, $replacement, $outdir);
		}

		$outdir_real = @realpath($outdir);

		// If the output folder is the default one (or any subdir), we are safe
		if (strpos($outdir_real, $default_out) !== false)
		{
			return false;
		}

		$component_path = @realpath(JPATH_ADMINISTRATOR . '/components/com_akeebabackup');

		$forbiddenPaths = [
			'akeeba',
			'engine',
			'forms',
			'installers',
			'language',
			'layouts',
			'platform',
			'services',
			'sql',
			'src',
			'tmpl',
		];

		foreach ($forbiddenPaths as $subdir)
		{
			$checkPath = realpath($component_path . '/' . $subdir);

			if ($checkPath === false)
			{
				continue;
			}

			$checkPath .= DIRECTORY_SEPARATOR;

			if (strpos($outdir_real, $checkPath) === 0)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Joomla 5+ with a custom public folder and Dereference Symlinks enabled
	 *
	 * @since  9.8.1
	 */
	public static function quirk_015()
	{
		$publicFolder = JoomlaPublicFolder::getPublicFolder();

		if ($publicFolder === JPATH_ROOT)
		{
			return false;
		}

		$registry = Factory::getConfiguration();

		return (bool) $registry->get('engine.archiver.common.dereference_symlinks', true);
	}

	/**
	 * Set the database driver object to be used by this platform class
	 *
	 * @param   DatabaseInterface  $dbDriver
	 *
	 * @since   9.3.0
	 */
	public static function setDbDriver(DatabaseInterface $dbDriver): void
	{
		self::$dbDriver = $dbDriver;
	}

	/**
	 * Get the database driver object used by this platform class
	 *
	 * @return  DatabaseInterface
	 *
	 * @since   9.3.0
	 */
	public static function getDbDriver(): DatabaseInterface
	{
		// If there is no DBO set we will go through the legacy part of the Joomla factory
		if (is_null(self::$dbDriver))
		{
			self::$dbDriver = JoomlaFactory::getContainer()->get(DatabaseInterface::class);
		}

		return self::$dbDriver;
	}

	/**
	 * Loads the current configuration off the database table
	 *
	 * @param   int  $profile_id  The profile where to read the configuration from, defaults to current profile
	 *
	 * @return  bool  True if everything was read properly
	 */
	public function load_configuration($profile_id = null, $reset = true)
	{
		// Load the configuration
		parent::load_configuration($profile_id, $reset);

		// If there is no embedded installer or the wrong embedded installer is selected, fix it automatically
		$config             = Factory::getConfiguration();
		$embedded_installer = $config->get('akeeba.advanced.embedded_installer', null);

		if (empty($embedded_installer) || ($embedded_installer == 'angie-joomla'))
		{
			$protectedKeys = $config->getProtectedKeys();
			$config->setProtectedKeys([]);
			$config->set('akeeba.advanced.embedded_installer', 'angie');
			$config->setProtectedKeys($protectedKeys);
		}

		return true;
	}

	/**
	 * Saves the current configuration to the database table
	 *
	 * @param   int  $profile_id  The profile where to save the configuration to, defaults to current profile
	 *
	 * @return  bool  True if everything was saved properly
	 */
	public function save_configuration($profile_id = null)
	{
		// If there is no embedded installer or the wrong embedded installer is selected, fix it automatically
		$config             = Factory::getConfiguration();
		$embedded_installer = $config->get('akeeba.advanced.embedded_installer', null);

		if (empty($embedded_installer) || ($embedded_installer == 'angie-joomla'))
		{
			$protectedKeys = $config->getProtectedKeys();
			$config->setProtectedKeys([]);
			$config->set('akeeba.advanced.embedded_installer', 'angie');
			$config->setProtectedKeys($protectedKeys);
		}

		// Save the configuration
		return parent::save_configuration($profile_id);
	}

	/**
	 * Performs heuristics to determine if this platform object is the ideal
	 * candidate for the environment Akeeba Engine is running in.
	 *
	 * @return bool
	 */
	public function isThisPlatform()
	{
		// Make sure _JEXEC is defined
		if (!defined('_JEXEC'))
		{
			return false;
		}

		// We need JVERSION to be defined
		if (!defined('JVERSION'))
		{
			return false;
		}

		// Check if the Joomla Factory class exists
		if (!class_exists('JFactory') && !class_exists('Joomla\CMS\Factory'))
		{
			return false;
		}

		// Check if a valid application class exists
		$appExists = class_exists('Joomla\CMS\Application\CMSApplication')
			|| class_exists('Joomla\CMS\Application\CliApplication');

		if (!$appExists)
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns an associative array of stock platform directories
	 *
	 * @return array
	 */
	public function get_stock_directories()
	{
		static $stock_directories = [];

		if (empty($stock_directories))
		{
			$app    = JoomlaFactory::getApplication();
			$tmpdir = $app->get('tmp_path');
            $host   = $this->get_host();

			$stock_directories['[SITEROOT]']       = $this->get_site_root();
			$stock_directories['[ROOTPARENT]']     = @realpath($this->get_site_root() . '/..');
			$stock_directories['[SITETMP]']        = $tmpdir;
			$stock_directories['[DEFAULT_OUTPUT]'] = $this->get_site_root() . '/administrator/components/com_akeebabackup/backup';
			$stock_directories['[HOST]']           = empty($host) ? 'unknown_host' : $host;
		}

		return $stock_directories;
	}

	/**
	 * Returns the absolute path to the site's root
	 *
	 * @return string
	 */
	public function get_site_root()
	{
		static $root = null;

		if (empty($root) || is_null($root))
		{
			$root = JPATH_ROOT;

			if (empty($root) || ($root == DIRECTORY_SEPARATOR) || ($root == '/'))
			{
				// Try to get the current root in a different way
				if (function_exists('getcwd'))
				{
					$root = getcwd();
				}

				if (JoomlaFactory::getApplication()->isClient('administrator'))
				{
					if (empty($root))
					{
						$root = '../';
					}
					else
					{
						$adminPos = strpos($root, 'administrator');

						if ($adminPos !== false)
						{
							$root = substr($root, 0, $adminPos);
						}
						else
						{
							$root = '../';
						}

						// Degenerate case where $root = 'administrator' without a leading slash before entering this
						// if-block
						if (empty($root))
						{
							$root = '../';
						}
					}
				}
				else
				{
					if (empty($root) || ($root == DIRECTORY_SEPARATOR) || ($root == '/'))
					{
						$root = './';
					}
				}
			}

			if (!in_array(substr($root, -1), ['/', '\\']))
			{
				$root .= DIRECTORY_SEPARATOR;
			}
		}

		return $root;
	}

	/**
	 * Returns the absolute path to the installer images directory
	 *
	 * @return string
	 */
	public function get_installer_images_path()
	{
		return JPATH_ADMINISTRATOR . '/components/com_akeebabackup/installers';
	}

	/**
	 * Returns the active profile number
	 *
	 * @return int
	 */
	public function get_active_profile()
	{
		// Automated testing override
		if (!is_null(self::$profile_id) && (self::$profile_id > 0))
		{
			return self::$profile_id;
		}
		// Constant override
		elseif (defined('AKEEBA_PROFILE'))
		{
			return AKEEBA_PROFILE;
		}
		// Use the session. If it's a CLI app always default to profile #1 (unless explicitly set otherwise)
		else
		{
			$defaultProfile = JoomlaFactory::getApplication()->isClient('cli') ? 1 : null;

			return JoomlaFactory::getApplication()->getSession()->get('akeebabackup.profile', $defaultProfile);
		}
	}

	/**
	 * Returns the selected profile's name. If no ID is specified, the current
	 * profile's name is returned.
	 *
	 * @return string
	 */
	public function get_profile_name($id = null)
	{
		if (empty($id))
		{
			$id = $this->get_active_profile();
		}

		$id = (int) $id;

		$db  = Factory::getDatabase($this->get_platform_database_options());
		$sql = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('description'))
			->from($db->qn('#__akeebabackup_profiles'))
			->where($db->qn('id') . ' = ' . $db->q($id));
		$db->setQuery($sql);

		return $db->loadResult();
	}

	/**
	 * Returns the backup origin
	 *
	 * @return string Backup origin: backend|frontend
	 */
	public function get_backup_origin()
	{
		if (defined('AKEEBA_BACKUP_ORIGIN'))
		{
			return AKEEBA_BACKUP_ORIGIN;
		}

		$app = JoomlaFactory::getApplication();

		if ($app->isClient('administrator'))
		{
			return 'backend';
		}

		if ($app->isClient('site'))
		{
			return 'frontend';
		}

		return 'cli';
	}

	/**
	 * Returns a MySQL-formatted timestamp out of the current date
	 *
	 * @param   string  $date  [optional] The timestamp to use. Omit to use current timestamp.
	 *
	 * @return  string
	 */
	public function get_timestamp_database($date = 'now')
	{
		return (new Date($date))->toSql();
	}

	/**
	 * Returns the current timestamp, taking into account any TZ information,
	 * in the format specified by $format.
	 *
	 * @param   string  $format  Timestamp format string (standard PHP format string)
	 *
	 * @return string
	 */
	public function get_local_timestamp($format)
	{
		// Do I have a forced timezone?
		$tz = $this->get_platform_configuration_option('forced_backup_timezone', 'AKEEBA/DEFAULT');

		// No forced timezone set? Use the default Joomla! behavior.
		if (empty($tz) || ($tz == 'AKEEBA/DEFAULT'))
		{
			$tz = $this->getJoomlaTimezone();
		}

		$utcTimeZone = new DateTimeZone('UTC');
		$dateNow     = clone JoomlaFactory::getDate('now', $utcTimeZone);
		$timezone    = new DateTimeZone($tz);

		return $dateNow->setTimezone($timezone)->format($format, true);
	}

	/**
	 * Returns the current host name
	 *
	 * @return string
	 */
	public function get_host()
	{
		if (JoomlaFactory::getApplication()->isClient('cli'))
		{
			$url  = Platform::getInstance()->get_platform_configuration_option('siteurl', '');
			$oURI = new Uri($url);
		}
		else
		{
			// Running under the web server
			$oURI = Uri::getInstance();
		}

		return $oURI->getHost();
	}

	public function get_site_name()
	{
		return JoomlaFactory::getApplication()->get('sitename', '');
	}

	/**
	 * Gets the best matching database driver class, according to CMS settings
	 *
	 * @param   bool  $use_platform  If set to false, it will forcibly try to assign one of the primitive type
	 *                               (Mysql/Mysqli) and NEVER tell you to use a platform driver.
	 *
	 * @return string
	 */
	public function get_default_database_driver($use_platform = true)
	{
		// Since I am always running inside a Joomla application I use our wrapper driver.
		return \Akeeba\Engine\Driver\Joomla::class;
	}

	/**
	 * Returns a set of options to connect to the default database of the current CMS
	 *
	 * @return array
	 */
	public function get_platform_database_options()
	{
		static $options;

		if (empty($options))
		{
			$app     = JoomlaFactory::getApplication();
			$options = [
				'host'     => $app->get('host'),
				'user'     => $app->get('user'),
				'password' => $app->get('password'),
				'database' => $app->get('db'),
				'prefix'   => $app->get('dbprefix'),
				'ssl'      => [],
			];

			if ((int) $app->get('dbencryption') !== 0)
			{
				$options['ssl'] = [
					'enable'             => true,
					'verify_server_cert' => (bool) $app->get('dbsslverifyservercert'),
				];

				foreach (['cipher', 'ca', 'key', 'cert'] as $value)
				{
					$confVal = trim($app->get('dbssl' . $value, ''));

					if ($confVal !== '')
					{
						$options['ssl'][$value] = $confVal;
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Provides a platform-specific translation function
	 *
	 * @param   string  $key  The translation key
	 *
	 * @return string
	 */
	public function translate($key)
	{
		/**
		 * The engine uses the legacy COM_AKEEBA_ prefix for b/c reasons. Version 9 and later use the COM_AKEEBABACKUP_
		 * prefix for language strings. This handles the automatic conversion between the two formats.
		 */
		$key = str_replace('COM_AKEEBA_', 'COM_AKEEBABACKUP_', strtoupper($key));

		return Text::_($key);
	}

	/**
	 * Populates global constants holding the Akeeba version
	 */
	public function load_version_defines()
	{
		$basePath = JPATH_ADMINISTRATOR . '/components/com_akeebabackup';

		if (file_exists($basePath . '/version.php'))
		{
			require_once($basePath . '/version.php');
		}

		if (!defined('AKEEBA_VERSION'))
		{
			define("AKEEBA_VERSION", "dev");
		}
		if (!defined('AKEEBA_PRO'))
		{
			define('AKEEBA_PRO', false);
		}
		if (!defined('AKEEBA_DATE'))
		{
			$date = clone JoomlaFactory::getDate();

			define("AKEEBA_DATE", $date->format('Y-m-d'));
		}
	}

	/**
	 * Returns the platform name and version
	 *
	 * @param   string  $platform_name  Name of the platform, e.g. Joomla!
	 * @param   string  $version        Full version of the platform
	 */
	public function getPlatformVersion()
	{
		$v = new Version();

		return [
			'name'    => 'Joomla!',
			'version' => $v->getShortVersion(),
		];
	}

	/**
	 * Logs platform-specific directories with LogLevel::INFO log level
	 */
	public function log_platform_special_directories()
	{
		$ret = [];

		Factory::getLog()->log(LogLevel::INFO, "JPATH_BASE         :" . JPATH_BASE, ['translate_root' => false]);
		Factory::getLog()->log(LogLevel::INFO, "JPATH_SITE         :" . JPATH_SITE, ['translate_root' => false]);
		Factory::getLog()->log(LogLevel::INFO, "JPATH_ROOT         :" . JPATH_ROOT, ['translate_root' => false]);
		Factory::getLog()->log(LogLevel::INFO, "JPATH_CACHE        :" . JPATH_CACHE, ['translate_root' => false]);
		Factory::getLog()->log(LogLevel::INFO, "Computed <root>    :" . $this->get_site_root(), ['translate_root' => false]);

		// If the release is older than 3 months, issue a warning
		if (defined('AKEEBA_DATE'))
		{
			$releaseDate = clone JoomlaFactory::getDate(AKEEBA_DATE);

			if (time() - $releaseDate->toUnix() > 10368000)
			{
				if (!isset($ret['warnings']))
				{
					$ret['warnings'] = [];
					$ret['warnings'] = array_merge($ret['warnings'], [
						'Your version of Akeeba Backup is more than 120 days old and most likely already out of date. Please check if a newer version is published and install it.',
					]);
				}
			}

		}

		// Detect UNC paths and warn the user
		if (DIRECTORY_SEPARATOR == '\\')
		{
			if ((substr(JPATH_ROOT, 0, 2) == '\\\\') || (substr(JPATH_ROOT, 0, 2) == '//'))
			{
				if (!isset($ret['warnings']))
				{
					$ret['warnings'] = [];
				}

				$ret['warnings'] = array_merge($ret['warnings'], [
					'Your site\'s root is using a UNC path (e.g. \\\\SERVER\\path\\to\\root). PHP has known bugs which may',
					'prevent it from working properly on a site like this. Please take a look at',
					'https://bugs.php.net/bug.php?id=40163 and https://bugs.php.net/bug.php?id=52376. As a result your',
					'backup may fail.',
				]);
			}
		}

		if (empty($ret))
		{
			$ret = null;
		}

		return $ret;
	}

	/**
	 * Loads a platform-specific software configuration option
	 *
	 * @param   string  $key
	 * @param   mixed   $default
	 *
	 * @return mixed
	 */
	public function get_platform_configuration_option($key, $default)
	{
		if (in_array($key, ['update_dlid', 'dlid', 'downloadid']))
		{
			return $this->getDownloadId('pkg_akeebabackup');
		}

		$value = ComponentHelper::getParams('com_akeebabackup')->get($key, $default);

		// Some configuration options may have to be decrypted
		switch ($key)
		{
			case 'frontend_secret_word':
				$secureSettings = Factory::getSecureSettings();
				$value          = $secureSettings->decryptSettings($value);
				break;
		}

		return $value;
	}

	/**
	 * Returns a list of emails to the Super Administrators
	 *
	 * @return  array
	 */
	public function get_administrator_emails()
	{
		$options = $this->get_platform_database_options();
		$db      = Factory::getDatabase($options);

		// Get all usergroups with Super User access
		$q      = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([$db->qn('id')])
			->from($db->qn('#__usergroups'));
		$groups = $db->setQuery($q)->loadColumn();

		// Get the groups that are Super Users
		$groups = array_filter($groups, function ($gid) {
			return Access::checkGroup($gid, 'core.admin');
		});

		$mails = [];

		foreach ($groups as $gid)
		{
			$uids = Access::getUsersByGroup($gid);
			array_walk($uids, function ($uid, $index) use (&$mails) {
				$mails[] = JoomlaFactory::getContainer()
					->get(UserFactoryInterface::class)
					->loadUserById($uid)
					->email;
			});
		}

		return array_unique($mails);
	}

	/**
	 * Sends a very simple email using the platform's mailer facility
	 *
	 * @param   string  $to          The recipient's email address
	 * @param   string  $subject     The subject of the email
	 * @param   string  $body        The body of the email
	 * @param   string  $attachFile  The file to attach (null to not attach any files)
	 *
	 * @return  boolean
	 */
	public function send_email($to, $subject, $body, $attachFile = null)
	{
		Factory::getLog()->log(LogLevel::DEBUG, "-- Fetching mailer object");

		/** @var Mail $mailer */
		try
		{
			$mailer = Platform::getInstance()->getMailer();
		}
		catch (Exception $e)
		{
			$mailer = null;
		}

		if (!is_object($mailer))
		{
			Factory::getLog()->log(LogLevel::WARNING, "Could not send email to $to - Joomla! cannot send e-mails. Please check your From EMail and From Name fields in Global Configuration.");

			return false;
		}

		Factory::getLog()->log(LogLevel::DEBUG, "-- Creating email message");

		try
		{
			$recipient = [$to];

			$mailer->addRecipient($recipient);
			$mailer->setSubject($subject);
			$mailer->setBody($body);
		}
		catch (Exception $e)
		{
			Factory::getLog()->log(LogLevel::WARNING, "Could not send email to $to - Problem setting up the email. Joomla! reports error: " . $e->getMessage());

			return false;
		}

		try
		{
			if (!empty($attachFile))
			{
				Factory::getLog()->log(LogLevel::INFO, "-- Attaching $attachFile");

				if (!file_exists($attachFile) || !(is_file($attachFile) || is_link($attachFile)))
				{
					Factory::getLog()->log(LogLevel::WARNING, "The file does not exist, or it's not a file; no email sent");

					return false;
				}

				if (!is_readable($attachFile))
				{
					Factory::getLog()->log(LogLevel::WARNING, "The file is not readable; no email sent");

					return false;
				}

				$filesize = @filesize($attachFile);

				if ($filesize)
				{
					// Check that we have AT LEAST 2.5 times free RAM as the filesize (that's how much we'll need)
					if (!function_exists('ini_get'))
					{
						// Assume 8Mb of PHP memory limit (worst case scenario)
						$totalRAM = 8388608;
					}
					else
					{
						$totalRAM = ini_get('memory_limit');
						if (strstr($totalRAM, 'M'))
						{
							$totalRAM = (int) $totalRAM * 1048576;
						}
						elseif (strstr($totalRAM, 'K'))
						{
							$totalRAM = (int) $totalRAM * 1024;
						}
						elseif (strstr($totalRAM, 'G'))
						{
							$totalRAM = (int) $totalRAM * 1073741824;
						}
						else
						{
							$totalRAM = (int) $totalRAM;
						}
						if ($totalRAM <= 0)
						{
							// No memory limit? Cool! Assume 1Gb of available RAM (which is absurdely abundant as of March 2011...)
							$totalRAM = 1086373952;
						}
					}
					if (!function_exists('memory_get_usage'))
					{
						$usedRAM = 8388608;
					}
					else
					{
						$usedRAM = memory_get_usage();
					}

					$availableRAM = $totalRAM - $usedRAM;

					if ($availableRAM < 2.5 * $filesize)
					{
						Factory::getLog()->log(LogLevel::WARNING, "The file is too big to be sent by email. Please use a smaller Part Size for Split Archives setting.");
						Factory::getLog()->log(LogLevel::DEBUG, "Memory limit $totalRAM bytes -- Used memory $usedRAM bytes -- File size $filesize -- Attachment requires approx. " . (2.5 * $filesize) . " bytes");

						return false;
					}
				}
				else
				{
					Factory::getLog()->log(LogLevel::WARNING, "Your server fails to report the file size of $attachFile. If the backup crashes, please use a smaller Part Size for Split Archives setting");
				}

				$mailer->addAttachment($attachFile);
			}
		}
		catch (Exception $e)
		{
			Factory::getLog()->log(LogLevel::WARNING, "Could not send email to $to - Problem attaching file. Joomla! reports error: " . $e->getMessage());

			return false;
		}

		Factory::getLog()->log(LogLevel::DEBUG, "-- Sending message");

		try
		{
			$result = $mailer->Send();
		}
		catch (Exception $e)
		{
			$result = $e;
		}

		if ($result instanceof Exception)
		{
			Factory::getLog()->log(LogLevel::WARNING, "Could not email $to:");
			Factory::getLog()->log(LogLevel::WARNING, $result->getMessage());
			$ret = $result->getMessage();
			unset($result);
			unset($mailer);

			return $ret;
		}

		Factory::getLog()->log(LogLevel::DEBUG, "-- Email sent");

		return true;
	}

	/**
	 * Deletes a file from the local server using direct file access or FTP
	 *
	 * @param   string  $file
	 *
	 * @return bool
	 */
	public function unlink($file)
	{
		// return (@unlink($file) === false) ? File::delete($file) : $result;

		return @unlink($file);
	}

	/**
	 * Moves a file around within the local server using direct file access or FTP
	 *
	 * @param   string  $from
	 * @param   string  $to
	 *
	 * @return bool
	 */
	public function move($from, $to)
	{
		$result = @rename($from, $to);

		if (!$result)
		{
			$result = @copy($from, $to) && @unlink($from);

			if (!$result)
			{
				@unlink($to);
			}
		}

		return $result;
	}

	/**
	 * Joomla!-specific function to get an instance of the mailer class
	 *
	 * @return Mail
	 */
	public function &getMailer()
	{
		/** @var Mail $mailer */
		$mailer = JoomlaFactory::getContainer()->get(MailerFactoryInterface::class)->createMailer();

		if (!is_object($mailer))
		{
			Factory::getLog()->log(LogLevel::WARNING, "Fetching Joomla!'s mailer was impossible; imminent crash!");
		}
		else
		{
			$emailMethod = $mailer->Mailer;
			Factory::getLog()->log(LogLevel::DEBUG, "-- Joomla!'s mailer is using $emailMethod mail method.");
		}

		return $mailer;
	}

	/**
	 * Stores a flash (temporary) variable in the session.
	 *
	 * @param   string  $name   The name of the variable to store
	 * @param   string  $value  The value of the variable to store
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function set_flash_variable($name, $value)
	{
		JoomlaFactory::getApplication()->getSession()->set(sprintf("akeebabackup.%s", $name), $value);
	}

	/**
	 * Return the value of a flash (temporary) variable from the session and
	 * immediately removes it.
	 *
	 * @param   string  $name     The name of the flash variable
	 * @param   mixed   $default  Default value, if the variable is not defined
	 *
	 * @return  mixed  The value of the variable or $default if it's not set
	 * @throws  Exception
	 */
	public function get_flash_variable($name, $default = null)
	{
		/** @var Session $session */
		$session   = JoomlaFactory::getApplication()->getSession();
		$flashName = sprintf("akeebabackup.%s", $name);
		$ret       = $session->get($flashName, $default);

		$session->remove($flashName);

		return $ret;
	}

	/**
	 * Perform an immediate redirection to the defined URL
	 *
	 * @param   string  $url  The URL to redirect to
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public function redirect($url)
	{
		/**
		 * Redirecting to bare "index.php?..." URLs in the backend of Joomla 4 and later results in a redirection taking
		 * place to a front-end URL. The trick below will automatically convert these relative URLs to absolute URLs
		 * which Joomla can now handle correctly.
		 */
		if (substr($url, 0, 9) === 'index.php')
		{
			$givenUri = new Uri($url);
			$newUri   = new Uri(Uri::base());

			$newUri->setQuery($givenUri->getQuery());

			if ($givenUri->getFragment())
			{
				$newUri->setFragment($givenUri->getFragment());
			}

			$url = $newUri->toString();
		}

		JoomlaFactory::getApplication()->redirect($url);
	}

	public function apply_quirk_definitions()
	{
		$configurationCheck = Factory::getConfigurationChecks();
		$configurationCheck->addConfigurationCheckDefinition('013', 'critical', 'COM_AKEEBABACKUP_CPANEL_WARNING_Q013', [
			Joomla::class, 'quirk_013',
		]);
		$configurationCheck->addConfigurationCheckDefinition('015', 'critical', 'COM_AKEEBABACKUP_CPANEL_WARNING_Q015', [
			Joomla::class, 'quirk_015',
		]);
	}

	/**
	 * Returns additional data to put in the installation/extrainfo.json file in the backup archive.
	 *
	 * @return  array
	 *
	 * @since   9.8.1
	 */
	public function get_extra_info(): array
	{
		return [
			// Does the site have a custom public directory?
			'custom_public'  => JoomlaPublicFolder::hasCustomPublicFolderAutoIncluded(),
			// Where Joomla files are saved in
			'JPATH_ROOT'     => JPATH_ROOT,
			// Where the custom root is placed in
			'JPATH_PUBLIC'   => JoomlaPublicFolder::getPublicFolder(),
		];
	}

	/** @inheritdoc  */
	protected function detectProxySettings()
	{
		try
		{
			$app = JoomlaFactory::getApplication();
		}
		catch (Exception $e)
		{
			$this->proxyEnabled                = false;
			$this->hasInitialisedProxySettings = true;
		}

		$enabled = $app->get('proxy_enable', false);
		$host    = $app->get('proxy_host', '');
		$port    = (int) $app->get('proxy_port', 8080);
		$user    = $app->get('proxy_user', '');
		$pass    = $app->get('proxy_pass', '');

		$this->setProxySettings($enabled, $host, $port, $user, $pass);
	}

	/**
	 * Get the applicable timezone in the same way Joomla! calculates it: if there is a logged in
	 * user with a specific timezone set, use it. Otherwise use the Server Timezone defined in the
	 * site's Global Configuration. If nothing is set there, use GMT instead.
	 *
	 * @return  string
	 */
	private function getJoomlaTimezone()
	{
		// Out ultimate default is the server timezone set up in the Global Configuration
		/** @var CMSApplication $app */
		$app = JoomlaFactory::getApplication();
		$tz  = $app->get('offset', 'GMT');

		// If this is a CLI script, tough luck, we can't use a different TZ
		if ($app->isClient('cli'))
		{
			return $tz;
		}

		// If it's a guest user they can't have a special TZ set, return.
		$user = $app->getIdentity() ?? JoomlaFactory::getContainer()->get(UserFactoryInterface::class)->loadUserById(0);

		if ($user->guest)
		{
			return $tz;
		}

		$tz = $user->getParam('timezone', $tz);

		return $tz;
	}

	/**
	 * Gets the Download ID for a given package
	 *
	 * @param   string  $package
	 */
	private function getDownloadId(string $package = 'pkg_akeebabackup')
	{
		static $cache = [];

		if (isset($cache[$package]))
		{
			return $cache[$package];
		}

		$dbOptions = $this->get_platform_database_options();
		$db        = Factory::getDatabase($dbOptions);

		// Get the package type
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('us.extra_query'))
			->from($db->qn('#__update_sites') . ' ' . $db->qn('us'))
			->innerJoin($db->qn('#__update_sites_extensions') . ' ' . $db->qn('ue') .
				' ON(' .
				$db->qn('ue.update_site_id') . ' = ' . $db->qn('us.update_site_id') .
				')'
			)
			->innerJoin($db->qn('#__extensions') . ' ' . $db->qn('e') .
				' ON(' .
				$db->qn('ue.extension_id') . ' = ' . $db->qn('e.extension_id') .
				')'
			)
			->where($db->qn('e.type') . ' = ' . $db->q('package'))
			->where($db->qn('e.element') . ' = ' . $db->q('pkg_akeebabackup'));
		try
		{
			$extraQuery = $db->setQuery($query)->loadResult() ?? '';
		}
		catch (Exception $e)
		{
			return '';
		}

		if (empty($extraQuery) || (strpos($extraQuery, 'dlid=') !== 0))
		{
			return '';
		}

		[$cache[$package],] = explode('&', substr($extraQuery, 5), 2);

		return $cache[$package];
	}
}
