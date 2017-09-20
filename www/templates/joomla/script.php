<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  2.0
 * @note   This class name collides with the Joomla core installer script class, if we start hitting issues this class goes away
 */
class JoomlaInstallerScript extends InstallerScript
{
	/**
	 * Extension script constructor.
	 *
	 * @since   2.0
	 */
	public function __construct()
	{
		$this->minimumJoomla = '3.8';
		$this->minimumPhp    = '5.4';

		$this->deleteFiles = [
			'/language/en-GB/en-GB.tpl_joomla.ini',
			'/language/en-GB/en-GB.tpl_joomla.sys.ini',
			'/templates/joomla/images/apple-touch-icon-114-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-144-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-57-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-72-precomposed.png',
		];
	}

	/**
	 * Function to perform changes during postflight
	 *
	 * @param   string                                        $type    The action being performed
	 * @param   Joomla\CMS\Installer\Adapter\TemplateAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.0.1
	 */
	public function postflight($type, $parent)
	{
		$this->removeFiles();
	}
}
