<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use SimpleSAML\Configuration;

/**
 * SSO
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoModelSso extends BaseDatabaseModel
{
	/**
	 * Load a list of identity providers.
	 *
	 * @return  array  List of identity providers.
	 *
	 * @since   1.0.0
	 */
	public function getIdentityProviderAliases(): array
	{
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('alias'))
			->from($db->quoteName('#__sso_profiles'));
		$db->setQuery($query);

		return $db->loadColumn();
	}

	/**
	 * Get the SimpleSAMLphp version.
	 *
	 * @return  string  The SimpleSAMLphp version.
	 *
	 * @since   1.0.0
	 */
	public function getSamlVersion(): string
	{
		$samlConfig = new Configuration([], '');

		return $samlConfig->getVersion();
	}
}
