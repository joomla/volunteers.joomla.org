<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeHandler;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\JoomlaPublicFolder;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Utility\BufferStreamHandler;
use Joomla\Component\Installer\Administrator\Helper\InstallerHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;
use RuntimeException;
use Throwable;

/**
 * Custom UpgradeModel handler for migrating from Akeeba Backup 7/8 to 9+
 *
 * @since  9.0.0
 */
class MigrateSettings
{
	/**
	 * The UpgradeModel instance we belong to.
	 *
	 * @var   UpgradeModel
	 * @since 9.0.0
	 */
	private $upgradeModel;

	/**
	 * Joomla database driver object
	 *
	 * @var   DatabaseInterface|DatabaseDriver
	 * @since 9.0.0
	 */
	private $dbo;

	/**
	 * Constructor.
	 *
	 * @param   UpgradeModel  $upgradeModel  The UpgradeModel instance we belong to
	 *
	 * @since   9.0.0
	 */
	public function __construct(UpgradeModel $upgradeModel, DatabaseDriver $dbo)
	{
		$this->upgradeModel = $upgradeModel;
		$this->dbo          = $dbo;
	}

	/**
	 * Do I need to migrate settings from pkg_akeeba?
	 *
	 * This will return true only if com_akeeba is installed AND I have not already migrated the settings.
	 *
	 * @return  bool
	 * @since   9.0.0
	 */
	public function onNeedsMigration(): bool
	{
		$cParams         = ComponentHelper::getParams('com_akeebabackup');
		$alreadyMigrated = $cParams->get('migrated_from_pkg_akeeba', 0) == 1;
		$hasPkgAkeeba    = !empty($this->upgradeModel->getExtensionId('com_akeeba'));

		return $hasPkgAkeeba && !$alreadyMigrated;
	}

	/**
	 * Do we have a compatible, old version of Akeeba Backup (com_akeeba) already installed on the site?
	 *
	 * This tries to load the version of the already installed version from the manifest cache of the old component. A
	 * version is compatible if it's Akeeba Backup 7 or 8 or a dev release we created anytime after January 1st, 2020
	 * i.e. after we had announced Akeeba Backup 7 as an upcoming release.
	 *
	 * @return  bool
	 * @since   9.0.0
	 */
	public function onHasCompatibleAkeebaVersion(): bool
	{
		// Get the extension ID for com_akeeba
		$eid = $this->upgradeModel->getExtensionId('com_akeeba');

		if (empty($eid))
		{
			return false;
		}

		// Get the cached manifest of the old component (com_akeeba)
		$db    = $this->dbo;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select([
				$db->quoteName('manifest_cache'),
			])
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_akeeba'));

		try
		{
			$manifestCacheJson = $db->setQuery($query)->loadResult();
		}
		catch (Exception $e)
		{
			return false;
		}

		// JSON decode the manifest
		$manifest = @json_decode($manifestCacheJson, true);

		if (empty($manifest))
		{
			return false;
		}

		// Get the creation date and version
		$creationDate = $manifest['creationDate'];
		$version      = $manifest['version'];

		// If it's version 7.0.0 or later we're golden.
		if (version_compare($version, '7.0.0', 'ge'))
		{
			return true;
		}

		try
		{
			$date = clone JoomlaFactory::getDate($creationDate);
		}
		catch (Exception $e)
		{
			return false;
		}

		// If we have a dev release it must be published after January 1st, 2020, i.e. the year must be 2020 or later.
		if ($date->year >= 2020)
		{
			return true;
		}

		// All checks failed. This is an old version.
		return false;
	}

	/**
	 * Migrate from Akeeba Backup 7.x / 8.x
	 *
	 * @return  bool  Always true
	 * @since   9.0.0
	 */
	public function onMigrateSettings(): bool
	{
		$this->migrateDownloadKey();
		$this->migrateComponentOptions();
		$this->migrateEngineKey();
		$this->migrateDatabaseData();
		$this->migrateDefaultBackupFolder();

		return true;
	}

	public function onInstall($type, $parent)
	{
		JoomlaPublicFolder::savePublicFolder();
	}

	public function onUpdate($type, $parent)
	{
		JoomlaPublicFolder::savePublicFolder();
	}

	/**
	 * Migrates the component options from com_akeeba to com_akeebabackup and mark everything as migrated.
	 *
	 * @return  void
	 * @since   9.0.0
	 */
	private function migrateComponentOptions(): void
	{
		$oldParams = ComponentHelper::getParams('com_akeeba');
		$cParams   = ComponentHelper::getParams('com_akeebabackup');

		foreach ($oldParams->getIterator() as $key => $value)
		{
			$cParams->set($key, $value);
		}

		$cParams->set('migrated_from_pkg_akeeba', 1);

		JoomlaFactory::getApplication()
			->bootComponent('com_akeebabackup')
			->getComponentParametersService()
			->save($cParams);
	}

	/**
	 * Migrate the Akeeba Engine settings encryption key from com_akeeba to com_akeebabackup.
	 *
	 * @return  void
	 * @since   9.0.0
	 */
	private function migrateEngineKey(): void
	{
		$oldKey = JPATH_ADMINISTRATOR . '/components/com_akeeba/BackupEngine/serverkey.php';
		$newKey = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/engine/serverkey.php';

		if (!@file_exists($oldKey) || !@is_file($oldKey) || !@is_readable($oldKey))
		{
			return;
		}

		@copy($oldKey, $newKey);
	}

	/**
	 * Migrate the database data from com_akeeba to com_akeebabackup.
	 *
	 * @return  void
	 * @since   9.0.0
	 */
	private function migrateDatabaseData(): void
	{
		// First, we will mass-migrate the data
		$tableMap = [
			'#__ak_profiles' => '#__akeebabackup_profiles',
			'#__ak_stats'    => '#__akeebabackup_backups',
			'#__ak_storage'  => '#__akeebabackup_storage',
		];

		$this->dbo->transactionStart();

		foreach ($tableMap as $oldTable => $newTable)
		{
			// Remove all data from the existing table
			$this->dbo->truncateTable($newTable);

			// Create and run an INSERT INTO ... SELECT query to make the migrate run as fast as possible.
			$outerQuery = (method_exists($this->dbo, 'createQuery') ? $this->dbo->createQuery() : $this->dbo->getQuery(true))
				->select('*')
				->from($this->dbo->quoteName($oldTable));

			if ($oldTable === '#__ak_profiles')
			{
				$outerQuery->select('1 AS ' . $this->dbo->quoteName('access'));
			}

			$innerQuery = 'INSERT INTO ' . $this->dbo->quoteName($newTable) . ' ' . ((string) $outerQuery);

			$this->dbo->setQuery($innerQuery)->execute();
		}

		try
		{
			$this->dbo->transactionCommit();
		}
		catch (Exception $e)
		{
		}
	}

	private function migrateDefaultBackupFolder(): void
	{
		$oldFolder = 'components/com_akeeba/backup';
		$newFolder = 'components/com_akeebabackup/backup';

		// STEP 0 - Load the migrated server key using our neat in-memory patching trick. We'll need it in step 1 below.
		$this->reloadEncryptionKey();

		// STEP 1 — Replace com_akeeba to com_akeebabackup in the absolute output directory path of each backup profile.
		foreach ($this->getBackupProfileIDs() as $profile)
		{
			// Load the profile configuration
			try
			{
				Platform::getInstance()->load_configuration($profile);
				$config = Factory::getConfiguration();
			}
			catch (Throwable $e)
			{
				// Your database is broken :(
				continue;
			}

			$outputDirectory = $config->get('akeeba.basic.output_directory', '[DEFAULT_OUTPUT]');

			if (strpos($outputDirectory, $oldFolder) === false)
			{
				continue;
			}

			$outputDirectory = str_replace($oldFolder, $newFolder, $outputDirectory);

			try
			{
				Platform::getInstance()->save_configuration($profile);
			}
			catch (Throwable $e)
			{
				// Your database is broken!
				continue;
			}
		}

		// STEP 2 — Replace com_akeeba to com_akeebabackup in the absolute file path of every backup record.
		$db    = $this->dbo;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->update($db->quoteName('#__akeebabackup_backups'))
			->set(
				$db->quoteName('absolute_path') . ' = REPLACE(' .
				$db->quoteName('absolute_path') . ', :find, :replace'
				. ')'
			)
			->bind(':find', $oldFolder)
			->bind(':replace', $newFolder);
		$db->setQuery($query)->execute();

		// STEP 3 — Move all backup archives, log files and so on
		$sourceDir = JPATH_ADMINISTRATOR . '/' . $oldFolder;
		$destDir   = JPATH_ADMINISTRATOR . '/' . $newFolder;

		try
		{
			$folders = Folder::files($sourceDir);
		}
		catch (Exception $e)
		{
			$folders = [];
		}

		foreach ($folders as $fileName)
		{
			$sourceFile = $sourceDir . '/' . $fileName;
			$destFile   = $destDir . '/' . $fileName;

			if (!@rename($sourceFile, $destFile))
			{
				// File::move($sourceFile, $destFile);
			}
		}
	}

	/**
	 * Returns the IDs of all backup profiles
	 *
	 * @return  array
	 * @since   9.0.0
	 */
	private function getBackupProfileIDs(): array
	{
		$db = $this->dbo;

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('id'))
			->from($db->quoteName('#__akeebabackup_profiles'));

		$profiles = $db->setQuery($query)->loadColumn() ?: [];

		return $profiles;
	}

	private function reloadEncryptionKey()
	{
		$newKey   = JPATH_ADMINISTRATOR . '/components/com_akeebabackup/engine/serverkey.php';
		$contents = @file_get_contents($newKey);

		if ($contents === false)
		{
			return;
		}

		$contents = str_replace('AKEEBA_SERVERKEY', 'AKEEBA_MIGRATED_SERVERKEY', $contents);

		BufferStreamHandler::stream_register();
		file_put_contents('buffer://serverkey.php', $contents);
		require_once 'buffer://serverkey.php';

		if (!defined('AKEEBA_MIGRATED_SERVERKEY'))
		{
			[$junk, $toProcess] = explode("'AKEEBA_MIGRATED_SERVERKEY',", $contents);
			$toProcess = trim($toProcess, " \t\n;php?>)");
			$toProcess = trim($toProcess, '\'');
			define('AKEEBA_MIGRATED_SERVERKEY', $toProcess);
		}

		$key = @base64_decode(AKEEBA_MIGRATED_SERVERKEY);

		if (empty($key))
		{
			return;
		}

		$secSettings = Factory::getSecureSettings();
		$secSettings->setKey($key);
	}

	private function migrateDownloadKey(): void
	{
		$oldEid = $this->getExtensionId('pkg_akeeba', 'package', 0);
		$newEid = $this->getExtensionId('pkg_akeebabackup', 'package', 0);

		if (empty($oldEid) || empty($newEid))
		{
			return;
		}

		$oldDLKey = $this->getDownloadKey($oldEid);
		$newDLKey = $this->getDownloadKey($newEid);

		// If I already have a key do nothing
		if ($newDLKey['valid'])
		{
			return;
		}

		// If the old version does not have a key or does not support keys the Joomla 4 fall back to component options
		$dlid = $oldDLKey['value'] ?? null;

		if (!$oldDLKey['valid'] || !$oldDLKey['supported'])
		{
			$dlid = ComponentHelper::getParams('com_akeeba')->get('update_dlid', null);
		}

		// No Download ID to migrate? Okay, then.
		if (empty($dlid))
		{
			return;
		}

		$this->setDownloadKey($newEid, $dlid);
	}

	private function getDownloadKey(int $extension_id): array
	{
		// Get the extension record
		$db        = $this->dbo;
		$extension = new \Joomla\CMS\Table\Extension($db);
		$extension->load($extension_id);

		// Joomla expects the extra_query key in the object. This comes from the update site
		if (method_exists($extension, 'set'))
		{
			/** @noinspection PhpDeprecationInspection */
			$extension->set('extra_query', $this->getExtraQuery($extension_id));
		}
		else
		{
			$extension->extra_query = $this->getExtraQuery($extension_id);
		}

		// Use the InstallerHelper::getDownloadKey to get the current download key
		return InstallerHelper::getDownloadKey($extension);
	}

	private function setDownloadKey(int $extension_id, ?string $downloadKey)
	{
		$dlKeyInfo = $this->getDownloadKey($extension_id);

		if (!($dlKeyInfo['supported'] ?? false))
		{
			throw new RuntimeException('The extension does not support Download Keys');
		}

		$extraQuery = empty($downloadKey) ? null : ($dlKeyInfo['prefix'] . $downloadKey . $dlKeyInfo['suffix']);

		$this->setExtraQuery($extension_id, $extraQuery);
	}

	private function getExtraQuery(int $extension_id): ?string
	{
		$db    = $this->dbo;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('update_site_id'))
			->from($db->quoteName('#__update_sites_extensions'))
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $extension_id, ParameterType::INTEGER);
		$usid  = $db->setQuery($query)->loadResult() ?: null;

		if (empty($usid))
		{
			return null;
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('extra_query'))
			->from($db->quoteName('#__update_sites'))
			->where($db->quoteName('update_site_id') . ' = :usid')
			->bind(':usid', $usid, ParameterType::INTEGER);

		return $db->setQuery($query)->loadResult() ?: null;
	}

	private function setExtraQuery(int $extension_id, ?string $extraQuery): void
	{
		$db    = $this->dbo;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('update_site_id'))
			->from($db->quoteName('#__update_sites_extensions'))
			->where($db->quoteName('extension_id') . ' = :eid')
			->bind(':eid', $extension_id, ParameterType::INTEGER);
		$usid  = $db->setQuery($query)->loadResult() ?: null;

		if (empty($usid))
		{
			throw new RuntimeException('Cannot find the update site for the extension');
		}

		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->update($db->quoteName('#__update_sites'))
			->set($db->quoteName('extra_query') . ' = ' . $db->quote($extraQuery))
			->where($db->quoteName('update_site_id') . ' = :usid')
			->bind(':usid', $usid, ParameterType::INTEGER);

		$db->setQuery($query)->execute();
	}

	private function getExtensionId($element, $type, $clientId): ?int
	{
		$db    = $this->dbo;
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = :type')
			->where($db->quoteName('element') . ' = :element')
			->where($db->quoteName('client_id') . ' = :client_id');
		$query->bind(':type', $type, ParameterType::STRING);
		$query->bind(':element', $element, ParameterType::STRING);
		$query->bind(':client_id', $clientId, ParameterType::INTEGER);

		return $db->setQuery($query)->loadResult() ?: null;
	}
}