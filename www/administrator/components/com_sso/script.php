<?php
/**
 * @package    SSO.Component
 *
 * @copyright  Copyright (C) 2017 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

/**
 * SSO script file.
 *
 * @since       1.0.0
 */
class Com_SsoInstallerScript
{
	/**
	 * Called after any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		// Copy the SimpleSAMLphp library to it's location
		$src = $adapter->getParent()->getPath('source');

		JFolder::copy($src . '/libraries', JPATH_LIBRARIES, '', true);

		// Check if we have a configuration file
		if (!JFile::exists(JPATH_LIBRARIES . '/simplesamlphp/config/config.php'))
		{
			JFile::copy(JPATH_LIBRARIES . '/simplesamlphp/config/config.dist', JPATH_LIBRARIES . '/simplesamlphp/config/config.php');
		}

		// Check if we have an authorization file
		if (!JFile::exists(JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php'))
		{
			JFile::copy(JPATH_LIBRARIES . '/simplesamlphp/config/authsources.dist', JPATH_LIBRARIES . '/simplesamlphp/config/authsources.php');
		}

		// Create the cert folder
		JFolder::create(JPATH_LIBRARIES . '/simplesamlphp/cert');

		return true;
	}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		// Remove the SimpleSAMLphp library
		JFolder::delete(JPATH_LIBRARIES . '/simplesamlphp');
	}
}
