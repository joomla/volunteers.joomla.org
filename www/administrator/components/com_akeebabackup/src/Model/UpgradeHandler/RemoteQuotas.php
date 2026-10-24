<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeHandler;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\AkeebaEngineTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Model\UpgradeModel;
use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

class RemoteQuotas
{
	use AkeebaEngineTrait;

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

	public function onUpdate(?string $type = null, ?InstallerAdapter $parent = null): void
	{
		// Get a list of all backup profiles
		$db         = $this->dbo;
		$query      = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->quoteName('id'))
			->from($db->quoteName('#__akeebabackup_profiles'));
		$profileIds = $db->setQuery($query)->loadColumn();

		// Normally this should never happen as we're supposed to have at least profile #1
		if (empty($profileIds))
		{
			return;
		}

		$this->loadAkeebaEngine($this->dbo);

		$platform       = Platform::getInstance();
		$currentProfile = $platform->get_active_profile();

		foreach ($profileIds as $profile)
		{
			// Load the profile configuration
			try
			{
				$platform->load_configuration($profile);
				$config = Factory::getConfiguration();
			}
			catch (\Throwable $e)
			{
				// Your database is broken :(
				continue;
			}

			if ($config->get('akeeba.quota.remote', 0) != 1)
			{
				continue;
			}

			// Transcribe the local quota to remote quota settings if the legacy "Enable remote quotas" option is on.
			$protected = $config->getProtectedKeys();
			$config->setProtectedKeys([]);

			$config->set('akeeba.quota.remote', null);
			$config->set('akeeba.quota.remotely.maxage.enable', $config->get('akeeba.quota.maxage.enable', 0));
			$config->set('akeeba.quota.remotely.maxage.maxdays', $config->get('akeeba.quota.maxage.maxdays', 31));
			$config->set('akeeba.quota.remotely.maxage.keepday', $config->get('akeeba.quota.maxage.keepday', 1));
			$config->set('akeeba.quota.remotely.enable_size_quota', $config->get('akeeba.quota.enable_size_quota', 0));
			$config->set('akeeba.quota.remotely.size_quota', $config->get('akeeba.quota.size_quota', 15728640));
			$config->set('akeeba.quota.remotely.enable_count_quota', $config->get('akeeba.quota.enable_count_quota', 1));
			$config->set('akeeba.quota.remotely.count_quota', $config->get('akeeba.quota.count_quota', 3));

			$config->setProtectedKeys($protected);

			// Save the changes
			try
			{
				$platform->save_configuration($profile);
			}
			catch (\Throwable $e)
			{
				// Your database is broken!
				continue;
			}
		}

		$platform->load_configuration($currentProfile);
	}
}