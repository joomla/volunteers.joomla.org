<?php
/**
 * @package    SSO.Component
 *
 * @copyright  Copyright (C) 2017 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Installer\InstallerScript;

defined('_JEXEC') or die;

/**
 * SSO script file.
 *
 * @since       1.0.0
 */
class Com_SsoInstallerScript extends InstallerScript
{
	/**
	 * Set the minimum versions.
	 *
	 * @return  void
	 *
	 * @since   1.2.0
	 */
	public function __construct()
	{
		$this->minimumPhp    = '7.2';
		$this->minimumJoomla = '3.9';

		$this->deleteFolders = [
			JPATH_LIBRARIES . '/simplesamlphp'
		];
	}

	/**
	 * Called after any type of action
	 *
	 * @param   string            $route    Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0.0
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		// Copy the SimpleSAMLphp library to it's location
		$src = $adapter->getParent()->getPath('source');

		Folder::copy($src . '/libraries', JPATH_LIBRARIES, '', true);

		// Create new folders
		Folder::create(JPATH_LIBRARIES . '/simplesamlphp/cache');
		Folder::create(JPATH_LIBRARIES . '/simplesamlphp/config');
		Folder::create(JPATH_LIBRARIES . '/simplesamlphp/metadata');
		Folder::create(JPATH_LIBRARIES . '/simplesamlphp/cert');

		// Check if we have a configuration file
		if (!File::exists(JPATH_LIBRARIES . '/simplesamlphp/config/config.php'))
		{
			File::copy(JPATH_LIBRARIES . '/simplesamlphp/config-templates/config.php',
				JPATH_LIBRARIES . '/simplesamlphp/config/config.php'
			);
		}

		// Check if we have an authorization file
		if (!File::exists(JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php'))
		{
			File::copy(JPATH_LIBRARIES . '/simplesamlphp/config-templates/authsources.php',
				JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php'
			);
		}

		// Enable the metarefresh module to be able to load the metadata
		touch(JPATH_LIBRARIES . '/simplesamlphp/modules/metarefresh/enable');

		// Enable the cron module to be able to load the metadata
		touch(JPATH_LIBRARIES . '/simplesamlphp/modules/cron/enable');

		return true;
	}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		$this->removeFiles();
	}
}
