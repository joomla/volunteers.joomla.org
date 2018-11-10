<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2018 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * SSO helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class SsoHelper
{
	/**
	 * Render submenu.
	 *
	 * @param   string  $vName  The name of the current view.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(Text::_('COM_SSO_DASHBOARAD'), 'index.php?option=com_sso&view=sso', $vName == 'sso');
		JHtmlSidebar::addEntry(Text::_('COM_SSO_CONFIG'), 'index.php?option=com_sso&view=config', $vName == 'config');
		JHtmlSidebar::addEntry(Text::_('COM_SSO_CERTIFICATE'), 'index.php?option=com_sso&view=certificate', $vName == 'certificate');
		JHtmlSidebar::addEntry(Text::_('COM_SSO_PROFILES'), 'index.php?option=com_sso&view=profiles', $vName == 'profiles');
	}

	/**
	 * Load the configuration settings.
	 *
	 * @param   string  $alias  The profile alias to load
	 *
	 * @return  Registry  A Registry object.
	 *
	 * @since   1.0.0
	 */
	public function getParams($alias = 'joomla')
	{
		$db = Factory::getDbo();

		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
		$db->setQuery($query);

		return new Registry($db->loadResult());
	}

	/**
	 * Process the attribute fields received from the IDP.
	 *
	 * @param   string  $authorizationSource  The name of the profile
	 * @param   array   $attributes           The list of attributes received from IDP
	 *
	 * @return array Associative array with user fields.
	 *
	 * @since   1.0.0
	 */
	public function processAttributes($authorizationSource, $attributes)
	{
		$userFields = array();

		// Load the field mapping
		$db    = Factory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('fieldmap'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($authorizationSource));
		$db->setQuery($query);
		$fieldMap = $db->loadResult();

		if (!$fieldMap)
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_MISSING_FIELDMAP'));
		}

		$map = json_decode($fieldMap);

		// Convert the nested maps to a usable array
		$mapFields = array();

		foreach ($map->fieldMap as $index => $fields)
		{
			$mapFields[$fields->fields->idpName] = $fields->fields->localName;
		}

		// Get the mapped fields
		foreach ($attributes as $path => $value)
		{
			if (array_key_exists($path, $mapFields))
			{
				$userFields[$mapFields[$path]] = $value[0];
			}
		}

		if (empty($userFields))
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_NO_ATTRIBUTES_FOUND'));
		}

		return $userFields;
	}
}
