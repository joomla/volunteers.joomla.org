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

class AngieToBrs
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

			$currentInstaller = $config->get('akeeba.advanced.embedded_installer', 'brs');

			if (strpos($currentInstaller, 'brs') === 0)
			{
				continue;
			}

			// Transcribe the local quota to remote quota settings if the legacy "Enable remote quotas" option is on.
			$protected = $config->getProtectedKeys();
			$config->setProtectedKeys([]);

			$newInstaller = str_replace('angie', 'brs', $currentInstaller);

			if (!in_array($newInstaller, ['brs', 'brs-generic']))
			{
				$newInstaller = 'brs';
			}

			$config->set('akeeba.advanced.embedded_installer', $newInstaller);

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