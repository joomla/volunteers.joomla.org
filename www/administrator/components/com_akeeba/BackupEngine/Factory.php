<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Base\Part;
use Akeeba\Engine\Core\Database;
use Akeeba\Engine\Core\Filters;
use Akeeba\Engine\Core\Kettenrad;
use Akeeba\Engine\Core\Timer;
use Akeeba\Engine\Driver\Base;
use Akeeba\Engine\Dump\Native;
use Akeeba\Engine\Postproc\PostProcInterface;
use Akeeba\Engine\Util\ConfigurationCheck;
use Akeeba\Engine\Util\Encrypt;
use Akeeba\Engine\Util\EngineParameters;
use Akeeba\Engine\Util\FactoryStorage;
use Akeeba\Engine\Util\FileLister;
use Akeeba\Engine\Util\FileSystem;
use Akeeba\Engine\Util\Logger;
use Akeeba\Engine\Util\PushMessagesInterface;
use Akeeba\Engine\Util\RandomValue;
use Akeeba\Engine\Util\SecureSettings;
use Akeeba\Engine\Util\Statistics;
use Akeeba\Engine\Util\TemporaryFiles;
use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;

// Try to kill errors display
if (function_exists('ini_set') && !defined('AKEEBADEBUG'))
{
	ini_set('display_errors', false);
}

// Make sure the class autoloader is loaded
require_once __DIR__ . '/Autoloader.php';

/**
 * The Akeeba Engine Factory class
 *
 * This class is responsible for instantiating all Akeeba Engine classes
 */
abstract class Factory
{
	/**
	 * The absolute path to Akeeba Engine's installation
	 *
	 * @var  string
	 */
	private static $root;

	/**
	 * Partial class names of the loaded engines e.g. 'archiver' => 'Archiver\\Jpa'. Survives serialization.
	 *
	 * @var  array
	 */
	private static $engineClassnames = [];

	/**
	 * A list of instantiated objects which will persist after serialisation / unserialisation
	 *
	 * @var   array
	 */
	private static $objectList = [];

	/**
	 * A list of instantiated objects which will NOT persist after serialisation / unserialisation
	 *
	 * @var   array
	 */
	private static $temporaryObjectList = [];

	/**
	 * The class to use for push messages
	 *
	 * @since 9.3.1
	 * @var   string
	 */
	private static $pushClassName = 'Util\\PushMessages';

	/**
	 * Gets a serialized snapshot of the Factory for safekeeping (hibernate)
	 *
	 * @return  string  The serialized snapshot of the Factory
	 */
	public static function serialize(): string
	{
		// Call _onSerialize in all objects known to the factory
		foreach (static::$objectList as $object)
		{
			if (method_exists($object, '_onSerialize'))
			{
				call_user_func([$object, '_onSerialize']);
			}
		}

		// Serialise an array with all the engine information
		$engineInfo = [
			'root'             => static::$root,
			'objectList'       => static::$objectList,
			'engineClassnames' => static::$engineClassnames,
			'pushClassname'    => static::$pushClassName,
		];

		// Serialize the factory
		return serialize($engineInfo);
	}

	/**
	 * Regenerates the full Factory state from a serialized snapshot (resume)
	 *
	 * @param   string  $serializedData  The serialized snapshot to resume from
	 *
	 * @return  void
	 */
	public static function unserialize(string $serializedData): void
	{
		static::nuke();

		$engineInfo = unserialize($serializedData);

		static::$root                = $engineInfo['root'] ?? '';
		static::$objectList          = $engineInfo['objectList'] ?? [];
		static::$engineClassnames    = $engineInfo['engineClassnames'] ?? [];
		static::$pushClassName       = $engineInfo['pushClassname'] ?? 'Utils\\PushMessages';
		static::$temporaryObjectList = [];
	}

	/**
	 * Reset the internal factory state, freeing all previously created objects
	 *
	 * @return  void
	 */
	public static function nuke()
	{
		foreach (static::$objectList as &$object)
		{
			$object = null;
		}

		foreach (static::$temporaryObjectList as &$object)
		{
			$object = null;
		}

		static::$objectList          = [];
		static::$temporaryObjectList = [];
	}

	/**
	 * Saves the engine state to temporary storage
	 *
	 * @param   string|null  $tag       The backup origin to save. Leave empty to get from already loaded Kettenrad
	 *                                  instance.
	 * @param   string|null  $backupId  The backup ID to save. Leave empty to get from already loaded Kettenrad
	 *                                  instance.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  When the state save fails for any reason
	 * @noinspection PhpUnused
	 */
	public static function saveState(?string $tag = null, ?string $backupId = null): void
	{
		$kettenrad = static::getKettenrad();
		$tag       = $tag ?: $kettenrad->getTag();
		$backupId  = $backupId ?: $kettenrad->getBackupId();

		$saveTag = rtrim($tag . '.' . ($backupId ?: ''), '.');
		$ret     = $kettenrad->getStatusArray();

		if ($ret['HasRun'] == 1)
		{
			Factory::getLog()->debug("Will not save a finished Kettenrad instance");

			return;
		}

		Factory::getLog()->debug("Saving Kettenrad instance $tag");

		// Save a Factory snapshot
		$factoryStorage = static::getFactoryStorage();

		$logger = static::getLog();
		$logger->resetWarnings();

		$serializedFactoryData = static::serialize();
		$memoryFileExtension   = 'php';
		$result                = $factoryStorage->set($serializedFactoryData, $saveTag, $memoryFileExtension);

		/**
		 * Some hosts, such as WPEngine, do not allow us to save the memory files in .php files. In this case we use the
		 * far more insecure .dat extension.
		 */
		if ($result === false)
		{
			$memoryFileExtension = 'dat';
			$result              = $factoryStorage->set($serializedFactoryData, $saveTag, $memoryFileExtension);
		}

		if ($result === false)
		{
			$saveKey      = $factoryStorage->get_storage_filename($saveTag, $memoryFileExtension);
			$errorMessage = "Cannot save factory state in storage, storage filename $saveKey";

			$logger->error($errorMessage);

			throw new RuntimeException($errorMessage);
		}
	}

	/**
	 * Loads the engine state from the storage (if it exists).
	 *
	 * When failIfMissing is true (default) an exception will be thrown if the memory file / database record is no
	 * longer there. This is a clear indication of an issue with the storage engine, e.g. the host deleting the memory
	 * files in the middle of the backup step. Therefore, we'll switch the storage engine type before throwing the
	 * exception.
	 *
	 * When failIfMissing is false we do NOT throw an exception. Instead, we do a hard reset of the backup factory. This
	 * is required by the resetState method when we ask it to reset multiple origins at once.
	 *
	 * @param   string|null  $tag            The backup origin to load
	 * @param   string|null  $backupId       The backup ID to load
	 * @param   bool         $failIfMissing  Throw an exception if the memory data is no longer there
	 *
	 * @return  void
	 */
	public static function loadState(?string $tag = null, ?string $backupId = null, bool $failIfMissing = true): void
	{
		/** @noinspection PhpUndefinedConstantInspection */
		$tag     = $tag ?: (defined('AKEEBA_BACKUP_ORIGIN') ? AKEEBA_BACKUP_ORIGIN : 'backend');
		$loadTag = rtrim($tag . '.' . ($backupId ?: ''), '.');

		// In order to load anything, we need to have the correct profile loaded. Let's assume
		// that the latest backup record in this tag has the correct profile number set.
		$config = static::getConfiguration();

		if (empty($config->activeProfile))
		{
			$profile = Platform::getInstance()->get_active_profile();

			if (empty($profile) || ($profile <= 1))
			{
				// Only bother loading a configuration if none has been already loaded
				$filters = [
					['field' => 'tag', 'value' => $tag],
				];

				if (!empty($backupId))
				{
					$filters[] = ['field' => 'backupid', 'value' => $backupId];
				}

				$statList = Platform::getInstance()->get_statistics_list([
						'filters' => $filters, 'order' => [
							'by' => 'id', 'order' => 'DESC',
						],
					]
				);

				if (is_array($statList))
				{
					$stat    = array_pop($statList) ?? [];
					$profile = $stat['profile_id'] ?? 1;
				}
			}

			Platform::getInstance()->load_configuration($profile);
		}

		$profile = $config->activeProfile;

		Factory::getLog()->open($loadTag);
		Factory::getLog()->debug("Kettenrad :: Attempting to load from database ($tag) [$loadTag]");

		$serializedFactory = static::getFactoryStorage()->get($loadTag);

		if ($serializedFactory === null)
		{
			if ($failIfMissing)
			{
				throw new RuntimeException("Akeeba Engine detected a problem while saving temporary data. Please restart your backup.", 500);
			}

			// There is no serialized factory. Nuke the in-memory factory.
			Factory::getLog()->debug(" -- Stored Akeeba Factory ($tag) [$loadTag] not found - hard reset");
			static::nuke();
			Platform::getInstance()->load_configuration($profile);
		}

		Factory::getLog()->debug(" -- Loaded stored Akeeba Factory ($tag) [$loadTag]");
		static::unserialize($serializedFactory);

		unset($serializedFactory);
	}

	// ========================================================================
	// Public factory interface
	// ========================================================================

	/**
	 * Resets the engine state, wiping out any pending backups and/or stale temporary data.
	 *
	 * The configuration parameters are:
	 *
	 * * global  `bool`  True to reset all backups, regardless of the origin or profile ID
	 * * log     `bool`  True to log our actions (default: false)
	 * * maxrun  `int`   Only backup records older than this number of seconds will be reset (default: 180)
	 *
	 * Special considerations:
	 *
	 * * If global = true all backups from all origins are taken into account to determine which ones are stuck (over
	 *   the maxrun threshold since their last database entry).
	 *
	 * * If global = false only backups from the current backup origin are taken into account.
	 *
	 * * If global = false AND the current origin is 'backend' all pending and idle backups with the 'backup' origin are
	 *   considered stuck regardless of their age. In other words, maxrun is effectively set to 0. The idea is that only
	 *   a single person, from a single browser, should be taking backend backups at a time. Resetting single origin
	 *   backups is only ever meant to be called by the consumer when starting a backup.
	 *
	 * * Corollary to the above: starting a frontend, CLI or JSON API backup with the same backup profile DOES NOT reset
	 *   a previously failed backup if the new backup starts less than 'maxrun' seconds since the last step of the
	 *   failed backup started.
	 *
	 * * The time information for the backup age is taken from the database, namely the backupend field. If no time
	 *   is recorded for the last step we use the backupstart field instead.
	 *
	 * @param   array  $config  Configuration parameters for the reset operation
	 *
	 * @return  void
	 * @throws  Exception
	 * @noinspection PhpUnused
	 */
	public static function resetState(array $config = []): void
	{
		$defaultConfig = [
			'global' => true,
			'log'    => false,
			'maxrun' => 180,
		];

		$config = (object) array_merge($defaultConfig, $config);

		// Pause logging if so desired
		if (!$config->log)
		{
			Factory::getLog()->pause();
		}

		// Get the origin to clear, depending on the 'global' setting
		$originTag = $config->global ? null : Platform::getInstance()->get_backup_origin();

		// Cache the factory before proceeding
		$factory = static::serialize();

		// Get all running backups for the selected origin (or all origins, if global was false).
		$runningList = Platform::getInstance()->get_running_backups($originTag);

		// Sanity check
		if (!is_array($runningList))
		{
			$runningList = [];
		}

		// If the current origin is 'backend' we assume maxrun = 0 per the method docblock notes.
		$maxRun = ($originTag == 'backend') ? 0 : $config->maxrun;

		// Filter out entries by backup age
		$now         = time();
		$cutOff      = $now - $maxRun;
		$runningList = array_filter($runningList, function (array $running) use ($cutOff, $maxRun) {
			// No cutoff time: include all currently running backup records
			if ($maxRun == 0)
			{
				return true;
			}

			// Try to get the last backup tick timestamp
			try
			{
				$backupTickTime = !empty($running['backupend']) ? $running['backupend'] : $running['backupstart'];
				$tz             = new DateTimeZone('UTC');
				$tstamp         = (new DateTime($backupTickTime, $tz))->getTimestamp();
			}
			catch (Exception $e)
			{
				$tstamp = Factory::getLog()->getLastTimestamp($running['origin']);
			}

			if (is_null($tstamp))
			{
				return false;
			}

			// Only include still running backups whose last tick was BEFORE the cutoff time
			return $tstamp <= $cutOff;
		});

		// Mark running backups as failed
		foreach ($runningList as $running)
		{
			// Delete the failed backup's leftover archive parts
			$filenames = Factory::getStatistics()->get_all_filenames($running, false);
			$filenames = is_null($filenames) ? [] : $filenames;
			$totalSize = 0;

			foreach ($filenames as $failedArchive)
			{
				if (!@file_exists($failedArchive))
				{
					continue;
				}

				$totalSize += (int) @filesize($failedArchive);
				Platform::getInstance()->unlink($failedArchive);
			}

			// Mark the backup failed
			$running['status']     = 'fail';
			$running['instep']     = 0;
			$running['total_size'] = empty($running['total_size']) ? $totalSize : $running['total_size'];
			$running['multipart']  = 0;

			Platform::getInstance()->set_or_update_statistics($running['id'], $running);

			// Remove the temporary data
			$backupId = isset($running['backupid']) ? ('.' . $running['backupid']) : '';

			self::removeTemporaryData($running['origin'] . $backupId);
		}

		// Reload the factory
		static::unserialize($factory);
		unset($factory);

		// Unpause logging if it was previously paused
		if (!$config->log)
		{
			Factory::getLog()->unpause();
		}
	}

	/**
	 * Returns an Akeeba Configuration object
	 *
	 * @return  Configuration  The Akeeba Configuration object
	 */
	public static function getConfiguration(): Configuration
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(Configuration::class);
	}

	/**
	 * Returns a statistics object, used to track current backup's progress
	 *
	 * @return  Statistics
	 */
	public static function getStatistics(): Statistics
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(Statistics::class);
	}

	/**
	 * Returns the currently configured archiver engine
	 *
	 * @param   bool  $reset  Should I try to forcibly create a new instance?
	 *
	 * @return  Archiver\Base|null
	 */
	public static function getArchiverEngine(bool $reset = false): ?Archiver\Base
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getEngineInstance(
			'archiver', 'akeeba.advanced.archiver_engine',
			'Archiver\\', 'Archiver\\Jpa',
			$reset
		);
	}

	/**
	 * Returns the currently configured dump engine
	 *
	 * @param   boolean  $reset  Should I try to forcibly create a new instance?
	 *
	 * @return  Dump\Base|Native|null
	 */
	public static function getDumpEngine(bool $reset = false): ?object
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getEngineInstance(
			'dump', 'akeeba.advanced.dump_engine',
			'Dump\\', 'Dump\\Native',
			$reset
		);
	}

	/**
	 * Returns the filesystem scanner engine instance
	 *
	 * @param   bool  $reset  Should I try to forcibly create a new instance?
	 *
	 * @return  Scan\Base|null  The scanner engine
	 */
	public static function getScanEngine(bool $reset = false): ?Scan\Base
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getEngineInstance(
			'scan', 'akeeba.advanced.scan_engine',
			'Scan\\', 'Scan\\Large',
			$reset
		);
	}

	/**
	 * Returns the current post-processing engine. If no class is specified we
	 * return the post-processing engine configured in akeeba.advanced.postproc_engine
	 *
	 * @param   string|null  $engine  The name of the post-processing class to forcibly return
	 *
	 * @return  PostProcInterface|null
	 */
	public static function getPostprocEngine(?string $engine = null): ?PostProcInterface
	{
		if (!is_null($engine))
		{
			static::$engineClassnames['postproc'] = 'Postproc\\' . ucfirst($engine);

			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return static::getObjectInstance(static::$engineClassnames['postproc']);
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getEngineInstance(
			'postproc', 'akeeba.advanced.postproc_engine',
			'Postproc\\', 'Postproc\\None',
			true
		);
	}

	// ========================================================================
	// Core objects which are part of the engine state
	// ========================================================================

	/**
	 * Returns an instance of the Filters feature class
	 *
	 * @return  Filters  The Filters feature class' object instance
	 */
	public static function getFilters(): Filters
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(Filters::class);
	}

	/**
	 * Returns an instance of the specified filter group class. Do note that it does not
	 * work with platform filter classes. They are handled internally by AECoreFilters.
	 *
	 * @param   string  $filter_name  The filter class to load, without AEFilter prefix
	 *
	 * @return  Filter\Base|null  The filter class' object instance
	 */
	public static function getFilterObject(string $filter_name): ?Filter\Base
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance('Filter\\' . ucfirst($filter_name));
	}

	/**
	 * Loads an engine domain class and returns its associated object
	 *
	 * @param   string  $domainName  The name of the domain, e.g. installer for AECoreDomainInstaller
	 *
	 * @return  Part|null
	 */
	public static function getDomainObject(string $domainName): ?Part
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance('Core\\Domain\\' . ucfirst($domainName));
	}

	/**
	 * Returns a database connection object. It's an alias of AECoreDatabase::getDatabase()
	 *
	 * !!! IMPORTANT !!!
	 * DO NOT STATIC TYPE THIS METHOD.
	 *
	 * Akeeba Backup for Joomla is using a decorator to the Joomla DB object which makes use of the magic __call method
	 * to proxy driver calls. As a result it cannot adhere to an object declaration or interface. Until this is
	 * refactored we have to keep this method untyped.
	 *
	 * @param   array|null  $options  Options to use when instantiating the database connection
	 *
	 * @return  Base
	 */
	public static function getDatabase(?array $options = null)
	{
		if (is_null($options))
		{
			$options = Platform::getInstance()->get_platform_database_options();
		}

		if (isset($options['username']) && !isset($options['user']))
		{
			$options['user'] = $options['username'];
		}

		return Database::getDatabase($options);
	}

	/**
	 * Returns a database connection object. It's an alias of AECoreDatabase::getDatabase()
	 *
	 * @param   array|null  $options  Options to use when instantiating the database connection
	 *
	 * @return  void
	 */
	public static function unsetDatabase(?array $options = null): void
	{
		if (is_null($options))
		{
			$options = Platform::getInstance()->get_platform_database_options();
		}

		$db = Database::getDatabase($options);
		$db->close();

		Database::unsetDatabase($options);
	}

	/**
	 * Get a reference to the Akeeba Engine's timer
	 *
	 * @return  Timer
	 */
	public static function getTimer(): Timer
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(Timer::class);
	}

	/**
	 * Get a reference to Akeeba Engine's main controller called Kettenrad
	 *
	 * @return  Kettenrad
	 */
	public static function getKettenrad(): Kettenrad
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(Kettenrad::class);
	}

	/**
	 * Returns an instance of the factory temporary storage class
	 *
	 * @return  FactoryStorage
	 */
	public static function getFactoryStorage(): FactoryStorage
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(FactoryStorage::class);
	}

	/**
	 * Returns an instance of the encryption class
	 *
	 * @return  Encrypt
	 */
	public static function getEncryption(): Encrypt
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(Encrypt::class);
	}

	/**
	 * Returns an instance of the crypto-safe random value generator class
	 *
	 * @return  RandomValue
	 */
	public static function getRandval(): RandomValue
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(RandomValue::class);
	}

	/**
	 * Returns an instance of the filesystem tools class
	 *
	 * @return  FileSystem
	 */
	public static function getFilesystemTools(): FileSystem
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(FileSystem::class);
	}

	/**
	 * Returns an instance of the filesystem tools class
	 *
	 * @return  FileLister
	 * @noinspection PhpUnused
	 */
	public static function getFileLister(): FileLister
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(FileLister::class);
	}

	// ========================================================================
	// Temporary objects which are not part of the engine state
	// ========================================================================

	/**
	 * Returns an instance of the engine parameters provider which provides information on scripting, GUI configuration
	 * elements and engine parts
	 *
	 * @return  EngineParameters
	 */
	public static function getEngineParamsProvider(): EngineParameters
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(EngineParameters::class);
	}

	/**
	 * Returns an instance of the log object
	 *
	 * @return  Logger
	 */
	public static function getLog(): Logger
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(Logger::class);
	}

	/**
	 * Returns an instance of the configuration checks object
	 *
	 * @return  ConfigurationCheck
	 */
	public static function getConfigurationChecks(): ConfigurationCheck
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(ConfigurationCheck::class);
	}

	/**
	 * Returns an instance of the secure settings handling object
	 *
	 * @return  SecureSettings
	 */
	public static function getSecureSettings(): SecureSettings
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(SecureSettings::class);
	}

	/**
	 * Returns an instance of the secure settings handling object
	 *
	 * @return  TemporaryFiles
	 */
	public static function getTempFiles(): TemporaryFiles
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getTempObjectInstance(TemporaryFiles::class);
	}

	/**
	 * Get the connector object for push messages
	 *
	 * !!! WARNING !!! DO NOT STATIC TYPE
	 *
	 * The object type may change using setPushClass.
	 *
	 * @return  PushMessagesInterface
	 */
	public static function getPush()
	{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return static::getObjectInstance(self::$pushClassName);
	}

	/**
	 * Set the push notifications helper class to use with this factory
	 *
	 * @param   string  $className  The classname to use
	 *
	 * @since   9.3.1
	 * @noinspection PhpUnused
	 */
	public static function setPushClass(string $className): void
	{
		self::$pushClassName = $className;
	}

	/**
	 * Returns the absolute path to Akeeba Engine's installation
	 *
	 * @return  string
	 */
	public static function getAkeebaRoot(): string
	{
		if (empty(static::$root))
		{
			static::$root = __DIR__;
		}

		return static::$root;
	}

	/**
	 * @param   string  $engineType  Engine type, e.g. 'archiver', 'postproc', ...
	 * @param   string  $configKey   Profile config key with configured engine e.g. 'akeeba.advanced.archiver_engine'
	 * @param   string  $prefix      Prefix for engine classes, e.g. 'Archiver\\'
	 * @param   string  $fallback    Fallback class if the configured one doesn't exist e.g. 'Archiver\\Jpa'. Empty for
	 *                               no fallback.
	 * @param   bool    $reset       Should I force-reload the engine? Default: false.
	 *
	 * @return  object|null  The Singleton engine object instance
	 */
	protected static function getEngineInstance(string $engineType, string $configKey, string $prefix, string $fallback, bool $reset = false): ?object
	{
		if (!$reset && !empty(static::$engineClassnames[$engineType]))
		{
			return static::getObjectInstance(static::$engineClassnames[$engineType]);
		}

		// Unset the existing engine object
		if (!empty(static::$engineClassnames[$engineType]))
		{
			static::unsetObjectInstance(static::$engineClassnames[$engineType]);
		}

		// Get the engine name from the backup profile, construct a class name and check if it exists
		$registry                              = static::getConfiguration();
		$engine                                = $registry->get($configKey);
		static::$engineClassnames[$engineType] = $prefix . ucfirst($engine);
		$object                                = static::getObjectInstance(static::$engineClassnames[$engineType]);

		// If the engine object does not exist, fall back to the default
		if (!empty($fallback) && !is_object($object))
		{
			static::unsetObjectInstance(static::$engineClassnames[$engineType]);

			static::$engineClassnames[$engineType] = $fallback;
		}

		return static::getObjectInstance(static::$engineClassnames[$engineType]);
	}

	/**
	 * Internal function which instantiates an object of a class named $class_name.
	 *
	 * @param   string  $className
	 *
	 * @return  object|null
	 */
	protected static function getObjectInstance(string $className): ?object
	{
		$className = trim($className, '\\');

		if (substr($className, 0, 14) === 'Akeeba\\Engine\\')
		{
			$searchClass = $className;
			$className = substr($className, 14);
		}
		else
		{
			$searchClass = '\\Akeeba\\Engine\\' . $className;
		}

		if (isset(static::$objectList[$className]))
		{
			return static::$objectList[$className];
		}

		static::$objectList[$className] = null;

		if (class_exists($searchClass))
		{
			static::$objectList[$className] = new $searchClass;
		}
		elseif (class_exists($className))
		{
			static::$objectList[$className] = new $className;
		}

		return static::$objectList[$className];
	}

	// ========================================================================
	// Handy functions
	// ========================================================================

	/**
	 * Internal function which removes the object of the class named $class_name
	 *
	 * @param   string  $className
	 *
	 * @return  void
	 */
	protected static function unsetObjectInstance(string $className): void
	{
		if (substr($className, 0, 14) === 'Akeeba\\Engine\\')
		{
			$className = substr($className, 14);
		}

		if (isset(static::$objectList[$className]))
		{
			static::$objectList[$className] = null;
			unset(static::$objectList[$className]);
		}
	}

	/**
	 * Internal function which instantiates an object of a class named $class_name. This is a temporary instance which
	 * will not survive serialisation and subsequent unserialisation.
	 *
	 * @param   string  $className
	 *
	 * @return  object|null
	 */
	protected static function getTempObjectInstance(string $className): ?object
	{
		$className = trim($className, '\\');

		if (substr($className, 0, 14) === 'Akeeba\\Engine\\')
		{
			$searchClass = $className;
			$className = substr($className, 14);
		}
		else
		{
			$searchClass = '\\Akeeba\\Engine\\' . $className;
		}

		if (!isset(static::$temporaryObjectList[$className]))
		{
			static::$temporaryObjectList[$className] = null;

			if (class_exists($searchClass))
			{
				static::$temporaryObjectList[$className] = new $searchClass;
			}
		}

		return static::$temporaryObjectList[$className];
	}

	/**
	 * Remote the temporary data for a specific backup tag.
	 *
	 * @param   string  $originTag  The backup tag to reset e.g. 'backend.id123' or 'frontend'.
	 *
	 * @return  void
	 */
	protected static function removeTemporaryData(string $originTag): void
	{
		static::loadState($originTag, null, false);
		// Remove temporary files
		Factory::getTempFiles()->deleteTempFiles();
		// Delete any stale temporary data
		static::getFactoryStorage()->reset($originTag);
	}
}

/**
 * Timeout handler. It is registered as a global PHP shutdown function.
 *
 * If a PHP reports a timeout we will log this before letting PHP kill us.
 */
function AkeebaTimeoutTrap(): void
{
	if (connection_status() >= 2)
	{
		Factory::getLog()->error('Akeeba Engine has timed out');
	}
}

register_shutdown_function("\\Akeeba\\Engine\\AkeebaTimeoutTrap");
