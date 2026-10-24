<?php
/**
 * @package     SSO.Component
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Sso;

defined('_JEXEC') or die;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Registry\Registry;

/**
 * SSO helper.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class Sso
{
	/**
	 * Load the configuration settings.
	 *
	 * @param   string  $alias  The profile alias to load
	 *
	 * @return  Registry  A Registry object.
	 *
	 * @since   1.0.0
	 */
	public function getParams(string $alias = 'joomla'): Registry
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->createQuery()
			->select($db->quoteName('params'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
		$db->setQuery($query);

		return new Registry($db->loadResult());
	}

    /**
     * Load the profile ID.
     *
     * @param   string  $alias  The profile alias to load
     *
     * @return  int|null  The profile ID
     *
     * @since   1.5.0
     */
    public function getProfileId(string $alias = 'joomla'):? int
    {
        $db = Factory::getContainer()->get('DatabaseDriver');

        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__sso_profiles'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
        $db->setQuery($query);

        return (int) $db->loadResult();
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
	 * @throws  Exception
	 */
	public function processAttributes(string $authorizationSource, array $attributes): array
	{
		$userFields = [];

        $db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery()
			->select($db->quoteName('fieldmap'))
			->from($db->quoteName('#__sso_profiles'))
			->where($db->quoteName('alias') . ' = ' . $db->quote($authorizationSource));
		$db->setQuery($query);
		$fieldMap = $db->loadResult();

		if (!$fieldMap)
		{
			throw new InvalidArgumentException(Text::_('COM_SSO_MISSING_FIELDMAP'));
		}

		$map = json_decode($fieldMap, false);

		// Convert the nested maps to a usable array
		$mapFields = [];

		foreach ($map->fieldMap as $fields)
		{
			$mapFields[$fields->fields->idpName] = $fields->fields->localName;
		}

		// Get the mapped fields
		foreach ($attributes as $path => $value)
		{
			$userFields = $this->addAttribute($path, $value, $userFields, $mapFields);
		}

		if (empty($userFields))
		{
			Factory::getApplication()->getLanguage()->load('com_sso', JPATH_ADMINISTRATOR . '/components/com_sso');
			throw new InvalidArgumentException(Text::_('COM_SSO_NO_ATTRIBUTES_FOUND'));
		}

		// Trigger plugins to do customizing of the attributes
		PluginHelper::importPlugin('sso');
        $event = new Event(
            'onAfterProcessAttributes',
            [&$userFields]
        );
        Factory::getApplication()->getDispatcher()->dispatch('onAfterProcessAttributes', $event);

		return $userFields;
	}

	/**
	 * Add attributes to the user fields.
	 *
	 * @param   string  $path        The attribute name received from the Identity Provider
	 * @param   mixed   $value       The attribute value received from the Identity Provider, this can be a string or an array
	 * @param   array   $userFields  The aggregated list of fields received from the Identity Provider translated to user defined names
	 * @param   array   $mapFields   The user group mapping
	 *
	 * @return  array  List of attributes ordered by user fields.
	 *
	 * @since   1.5.0
	 */
	private function addAttribute(string $path, mixed $value, array $userFields, array $mapFields): array
	{
		if (is_array($value))
		{
			foreach ($value as $single)
			{
				$this->addAttribute($path, $single, $userFields, $mapFields);
			}
		}

		if (isset($mapFields[$path]))
		{
			$userFields[$mapFields[$path]] = $value;
		}

		return $userFields;
	}
}
