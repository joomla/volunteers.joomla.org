<?php
/**
 * @package     SSO
 * @subpackage  Install
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Script to run on installation of RO SSO package.
 *
 * @package  SSO
 * @since    1.0.0
 */
class Pkg_SsoInstallerScript extends InstallerScript
{
	/**
	 * Extension script constructor.
	 *
	 * @since   1.2.0
	 */
	public function __construct()
	{
		$this->minimumJoomla = '3.9';
		$this->minimumPhp    = '7.1';
	}
}
