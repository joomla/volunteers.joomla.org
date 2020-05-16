<?php
/**
 * @package     SSO
 * @subpackage  Install
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

defined('_JEXEC') or die;

/**
 * Script to run on installation of RO SSO package.
 *
 * @package     SSO
 * @subpackage  Install
 * @since       1.0.0
 */
class Pkg_SsoInstallerScript
{
	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 *
	 * @since 7.0
	 */
	protected $minimumPHPVersion = '7.0';

	/**
	 * The minimum Joomla version required to install this extension
	 *
	 * @var   string
	 *
	 * @since 7.0
	 */
	protected $minimumJoomlaVersion = '3.8.0';

	/**
	 * Method to run after an install/update/uninstall method.
	 *
	 * @param   string  $type    The type of installation being done
	 * @param   object  $parent  The parent calling class
	 *
	 * @return  bool  True on success | False if a version is not supported.
	 *
	 * @since   7.0
	 */
	public function preflight($type, $parent)
	{
		if (defined('PHP_VERSION'))
		{
			$version = PHP_VERSION;
		}
		elseif (function_exists('phpversion'))
		{
			$version = phpversion();
		}
		else
		{
			// No idea, we assume the PHP version is supported
			$version = '7.0';
		}

		if (!version_compare($version, $this->minimumPHPVersion, 'ge'))
		{
			$msg = "<p>You need PHP $this->minimumPHPVersion or later to install this package</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		// Check the minimum Joomla! version
		if (!empty($this->minimumJoomlaVersion) && !version_compare(JVERSION, $this->minimumJoomlaVersion, 'ge'))
		{
			$msg = "<p>You need Joomla! $this->minimumJoomlaVersion or later to install this package</p>";

			JLog::add($msg, JLog::WARNING, 'jerror');

			return false;
		}

		return true;
	}
}
