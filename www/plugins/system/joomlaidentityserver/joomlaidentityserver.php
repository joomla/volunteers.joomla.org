<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentityserver
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Utilities\ArrayHelper;

require_once JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php';

defined('_JEXEC') or die;

/**
 * Joomla Identity Plugin class
 *
 * @since 1.0.0
 */
class PlgSystemJoomlaIdentity extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    CMSApplication
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabase
	 * @since  1.0.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Saves user profile data
	 *
	 * @param   array    $data    entered user data
	 * @param   boolean  $isNew   true if this is a new user
	 * @param   boolean  $result  true if saving the user worked
	 * @param   string   $error   error message
	 *
	 * @return  boolean
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function onUserAfterSave($data, $isNew, $result, $error)
	{
		// Only act on new users
		if (!$isNew)
		{
			return true;
		}

		// Check if we have a valid user ID
		$userId = ArrayHelper::getValue($data, 'id', 0, 'int');

		if (!$userId)
		{
			$this->_subject->setError(Text::_('PLG_SYSTEM_JOOMLAIDENTITYSERVER_NO_USERID'));

			return false;
		}

		// Generate a GUID
		$helper = new IdentityHelper;
		$guid   = $helper->generateGuid();

		// Add the user to the profiles table
		$db    = $this->db;
		$query = $db->getQuery(true)
			->insert($db->quoteName('#__identity_profiles'))
			->columns(
				$db->quoteName(
					array(
						'user_id',
						'guid',
						'fullname'
					)
				)
			)
			->values($userId . ',' . $db->quote($guid) . ',' . $db->quote($data['name']));
		$db->setQuery($query)
			->execute();

		// Update the username with the GUID
		$query->clear()
			->update($db->quoteName('#__users'))
			->set($db->quoteName('username') . ' = ' . $db->quote($guid))
			->where($db->quoteName('id') . ' = ' . (int) $userId);
		$db->setQuery($query)
			->execute();

		// Require the helper
		require_once JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php';
		$helper = new IdentityHelper;

		// Get the general consent site
		$siteId = (int) $this->params->get('generalConsent', 0);

		// Check if there is any general consent to store
		if ($siteId === 0)
		{
			return true;
		}

		// Store the general consents
		$consents = $helper->loadSiteConsents($siteId);

		foreach ($consents as $consent)
		{
			if ($helper->isConsentStored($consent->id, $guid))
			{
				continue;
			}

			$helper->storeConsent($consent->id, $guid);
		}

		return true;
	}

	/**
	 * Delete user related data when user account is going to be deleted
	 *
	 * @param   array  $user  Holds the user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onUserBeforeDelete($user): void
	{
		// Empty the consents table
		$db    = $this->db;
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__identity_consents'))
			->where($db->quoteName('guid') . ' = ' . $db->quote($user['username']));
		$db->setQuery($query)->execute();

		// Empty the profiles table
		$query->clear()
			->delete($db->quoteName('#__identity_profiles'))
			->where($db->quoteName('guid') . ' = ' . $db->quote($user['username']));
		$db->setQuery($query)->execute();

		// Ping the remote sites
		$helper = new IdentityHelper;
		$helper->pingSites($user['username'], 'profile.delete');
	}
}
