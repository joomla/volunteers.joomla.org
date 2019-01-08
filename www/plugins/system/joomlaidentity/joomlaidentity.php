<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentity
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserHelper;
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
	 * Triggered before Joomla! renders the page
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onAfterRoute()
	{
		// Run on frontend only
		if ($this->app->isClient('administrator'))
		{
			return;
		}

		// Check for Joomla Identity
		$joomlaIdentity = $this->app->input->getCmd('joomlaidp');

		// Only continue for Joomla Identity
		if (!isset($joomlaIdentity))
		{
			return;
		}

		try
		{
			// Get the data from the payload
			$apiKey = $this->app->input->json->get('apiKey');

			// Check if the API key matches
			if (strlen($apiKey) <> 36
				&& $this->params->get('apikey') !== $apiKey)
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_INVALID_APIKEY'));
			}

			$guid    = $this->app->input->json->getString('guid');
			$consent = $this->app->input->json->getInt('consent', 0);
			$data    = (object) $this->app->input->json->get('data', array(), 'array');

			// Check if we have the minimum required data
			if (!isset($data->email))
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_MISSING_EMAIL'));
			}

			if (!$guid || strlen($guid) <> 36)
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_INVALID_GUID'));
			}

			if ($data)
			{
				$this->processIdentity($guid, $consent, $data);
			}
		}
		catch (Exception $exception)
		{
			echo $exception->getMessage();
		}

		// Since we decided to handle this request, we will close it as well
		$this->app->close();
	}

	/**
	 * Method to process the Joomla Identity data
	 *
	 * @param   string  $guid    The User ID
	 * @param   integer $consent Consent given or nog
	 * @param   object  $data    Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function processIdentity($guid, $consent, $data)
	{
		// Set anonymize name if no consent is provided
		if ($consent === 0)
		{
			$data->name  = 'Guest ' . substr($guid, 0, 8);
			$data->email = substr($guid, 0, 8) . '@identity.joomla.org';
		}

		// Update the Joomla user
		$this->updateJoomlaUser($guid, $data->name, $data->email);

		// Get Joomla user ID
		$userId = (int) UserHelper::getUserId($guid);

		// Plugin trigger onProcessIdentity for site specific processing
		\JEventDispatcher::getInstance()->trigger('onProcessIdentity', array($userId, $guid, $data));
	}

	/**
	 * Method to update the Joomla User
	 *
	 * @param   string $username Username (guid)
	 * @param   string $name     Name of user
	 * @param   string $email    Email of user
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function updateJoomlaUser($username, $name, $email)
	{
		// Consent date
		$user = (object) array(
			'username' => $username,
			'name'     => $name,
			'email'    => $email,
		);

		try
		{
			Factory::getDbo()->updateObject('#__users', $user, 'username');
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
		}
	}

	/**
	 * We prevent editing the user properties via the site itself
	 *
	 * @param   JForm $form The form to be altered.
	 * @param   mixed $data The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		// Disable editing the name, username, email, password and requireReset fields
		if ($this->params->get('isIdentityProvider'))
		{
			return true;
		}

		$form->setFieldAttribute('name', 'readonly', 'readonly');
		$form->setFieldAttribute('username', 'readonly', 'readonly');
		$form->setFieldAttribute('email', 'readonly', 'readonly');
		$form->setFieldAttribute('password', 'readonly', 'readonly');
		$form->setFieldAttribute('password2', 'readonly', 'readonly');
		$form->setFieldAttribute('requireReset', 'readonly', 'readonly');

		return true;
	}

	/**
	 * Saves user profile data
	 *
	 * @param   array   $data   entered user data
	 * @param   boolean $isNew  true if this is a new user
	 * @param   boolean $result true if saving the user worked
	 * @param   string  $error  error message
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
			$this->_subject->setError(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_NO_USERID'));

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

		// Get the general consents for this site
		if (!$this->params->get('isIdentityProvider'))
		{
			return true;
		}

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
	 * We do not allow deleting users
	 *
	 * @param   array $user Holds the user data
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
