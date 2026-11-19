<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeHandler;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Helper\DbQuery;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;
use Throwable;

/**
 * Custom UpgradeModel handler for migrating backup profiles away from the removed OneDrive (LEGACY) and OneDrive
 * (App-Specific Folder) post-processing engines.
 *
 * @since  10.5.0
 */
class MigrateOneDriveEngines
{
	/**
	 * The UpgradeModel instance we belong to.
	 *
	 * @var   UpgradeModel
	 * @since 10.5.0
	 */
	private $upgradeModel;

	/**
	 * Joomla database driver object
	 *
	 * @var   DatabaseDriver
	 * @since 10.5.0
	 */
	private $dbo;

	/**
	 * Constructor.
	 *
	 * @param   UpgradeModel    $upgradeModel  The UpgradeModel instance we belong to
	 * @param   DatabaseDriver  $dbo           Joomla database driver object
	 *
	 * @since   10.5.0
	 */
	public function __construct(UpgradeModel $upgradeModel, DatabaseDriver $dbo)
	{
		$this->upgradeModel = $upgradeModel;
		$this->dbo          = $dbo;
	}

	/**
	 * Migrate backup profiles using the removed OneDrive (LEGACY) or OneDrive (App-Specific Folder) engines to the
	 * OneDrive (Business) engine, blanking the now invalid authentication tokens of the removed engines.
	 *
	 * @param   string  $type    The type of change (install, update or discover_install)
	 * @param   object  $parent  The class calling this method
	 *
	 * @return  void
	 * @since   10.5.0
	 */
	public function onUpdate($type, $parent): void
	{
		try
		{
			$this->migrateProfiles();
		}
		catch (Throwable $e)
		{
			// If we cannot run the migration at all fail silently; the upgrade must not break.
		}
	}

	/**
	 * Migrate the affected backup profiles.
	 *
	 * @return  void
	 * @since   10.5.0
	 */
	private function migrateProfiles(): void
	{
		$db    = $this->dbo;
		$query = DbQuery::create($db)
			->select([
				$db->quoteName('id'),
				$db->quoteName('description'),
				$db->quoteName('configuration'),
			])
			->from($db->quoteName('#__akeebabackup_profiles'));

		$profiles = $db->setQuery($query)->loadObjectList() ?: [];

		$affectedProfiles = [];

		foreach ($profiles as $profile)
		{
			try
			{
				if ($this->migrateProfile((int) $profile->id, $profile->description, $profile->configuration))
				{
					$affectedProfiles[] = !empty($profile->description)
						? $profile->description
						: sprintf('Profile #%d', (int) $profile->id);
				}
			}
			catch (Throwable $e)
			{
				// A single broken profile must not break the upgrade.
			}
		}

		if (empty($affectedProfiles))
		{
			return;
		}

		Factory::getApplication()->enqueueMessage(
			Text::sprintf(
				'COM_AKEEBABACKUP_UPGRADE_MIGRATED_ONEDRIVE_ENGINES',
				implode(', ', $affectedProfiles)
			),
			'warning'
		);
	}

	/**
	 * Migrate a single backup profile's configuration, if it uses one of the removed engines.
	 *
	 * @param   int     $id             The profile ID
	 * @param   string  $description    The profile description
	 * @param   string  $configuration  The raw JSON-encoded profile configuration
	 *
	 * @return  bool  True if the profile was migrated
	 * @since   10.5.0
	 */
	private function migrateProfile(int $id, ?string $description, ?string $configuration): bool
	{
		$config = json_decode((string) $configuration, true);

		if (!is_array($config))
		{
			return false;
		}

		$engine = $config['akeeba.advanced.postproc_engine'] ?? null;

		if (!in_array($engine, ['onedrive', 'onedriveapp'], true))
		{
			return false;
		}

		// Switch the profile to the OneDrive (Business) engine
		$config['akeeba.advanced.postproc_engine'] = 'onedrivebusiness';

		// Blank the tokens of the removed engine; they are stored as plain strings
		$config[sprintf('engine.postproc.%s.access_token', $engine)]  = '';
		$config[sprintf('engine.postproc.%s.refresh_token', $engine)] = '';

		$db         = $this->dbo;
		$jsonConfig = json_encode($config);
		$query      = DbQuery::create($db)
			->update($db->quoteName('#__akeebabackup_profiles'))
			->set($db->quoteName('configuration') . ' = :configuration')
			->where($db->quoteName('id') . ' = :id')
			->bind(':configuration', $jsonConfig)
			->bind(':id', $id, ParameterType::INTEGER);
		$db->setQuery($query)->execute();

		return true;
	}
}
