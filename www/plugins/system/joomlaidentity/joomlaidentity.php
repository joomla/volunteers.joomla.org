<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentity
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

// No direct access.
defined('_JEXEC') or die;

/**
 * Joomla Identity Plugin class
 *
 * @since 1.0
 */
class PlgSystemJoomlaIdentity extends CMSPlugin
{
	/**
	 * JFactory::getApplication();
	 *
	 * @var    object  Joomla application object
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Extend the user object with identity data
	 *
	 * @param   object $user Joomla User Object
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	public function onUserAfterLoad($user)
	{
		$userId = isset($user->id) ? $user->id : 0;

		// Only continue for users with id
		if ($userId > 0)
		{
			// Get Identity from cache
			$identity = $this->getIdentityCache($user->username);

			// Set consent
			$user->consent = $identity->consent;

			// Override User Object data
			$user->name  = $identity->data->name;
			$user->email = $identity->data->email;

			// Unset in identity data
			unset($identity->data->name);
			unset($identity->data->email);

			// Add identity fields to User Object
			$user->identity = $identity->data;
		}

		return true;
	}

	/**
	 * Method to get identity data from cache
	 *
	 * @param   string $guid User GUID
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	private function getIdentityCache($guid)
	{
		$db      = Factory::getDbo();
		$nowDate = Factory::getDate()->toSql();

		// Load data from cache
		$query = $db->getQuery(true)
			->select($db->quoteName(array('data', 'consent')))
			->from($db->quoteName('#__identity_cache'))
			->where($db->quoteName('guid') . ' = ' . $db->quote($guid))
			->where($db->quoteName('expires') . ' >= ' . $db->quote($nowDate));

		try
		{
			$identity = $db->setQuery($query)->loadObject();
		}
		catch (RuntimeException $e)
		{
			Factory::getApplication()->enqueueMessage(Text::_('JERROR_AN_ERROR_HAS_sOCCURRED'), 'error');

			return new stdClass;
		}

		// Decode JSON
		if (isset($identity->data))
		{
			$identity->data = json_decode($identity->data);
		}

		// No data from cache, we need to get it from the API
		if (empty($identity))
		{
			$identity = $this->getIdentityApi($guid);
		}

		// Prepare identity data for rendering
		$identity = $this->prepareIdentity($guid, $identity);

		return $identity;
	}

	/**
	 * Method to get identity data from API
	 *
	 * @param   string $guid User GUID
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  Exception
	 */
	private function getIdentityApi($guid)
	{
		// Dummy API Response CONSENT OK @TODO
		//$response = file_get_contents(JPATH_ROOT . '/identity-consent-ok.json');

		// Dummy API Response CONSENT NOT OK @TODO
		$response = file_get_contents(JPATH_ROOT . '/identity-consent-notok.json');

		// Decode response to identity
		$identity = json_decode($response);

		// Store in cache
		$this->saveIdentityCache($guid, $identity->data, $identity->consent);

		return $identity;
	}


	/**
	 * Method to prepare Identity data
	 *
	 * @param   string $guid     User GUID
	 * @param   object $identity Identity data
	 *
	 * @return  object $identity profile
	 *
	 * @since   1.0
	 */
	private function prepareIdentity($guid, $identity)
	{
		// Add guest name and email
		if ($identity->consent == 0)
		{
			$identity->data->name  = 'Guest ' . substr($guid, 0, 8);
			$identity->data->email = substr($guid, 0, 8) . '@identity.joomla.org';
		}

		// @TODO we might want to add a plugin trigger here

		return $identity;
	}

	/**
	 * Method to save Identity Cache
	 *
	 * @param   string  $guid     User GUID
	 * @param   array   $identity Identity data
	 * @param   integer $consent  Consent for property
	 *
	 * @return boolean
	 *
	 * @since 1.0
	 * @throws Exception
	 */
	private function saveIdentityCache($guid, $identity, $consent)
	{
		$db = Factory::getDbo();

		// Set expire time
		$date = new Date('now +1 day');

		// Consent date
		$data = (object) array(
			'guid'    => $guid,
			'data'    => json_encode($identity),
			'consent' => $consent,
			'expires' => $date->toSql(),
		);

		try
		{
			$db->insertObject('#__identity_cache', $data, 'guid');
		}
		catch (Exception $e)
		{
			$db->updateObject('#__identity_cache', $data, 'guid');
		}

		return true;
	}
}
