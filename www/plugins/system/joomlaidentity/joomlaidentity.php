<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentity
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserHelper;
use Joomla\Utilities\ArrayHelper;

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
		$joomlaIdentity = $this->app->input->getBool('joomlaidp');

		// Only continue for Joomla Identity
		if (!isset($joomlaIdentity))
		{
			return;
		}

		try
		{
			// Get the hash from the payload
			$hash       = $this->app->input->getBase64('hash');
			$encodeData = $this->app->input->get('data', '', 'RAW');

			// Create the hash
			$calculatedHash = base64_encode(hash_hmac('sha512', $encodeData, $this->params->get('apikey')));

			// Check if the GUID and the hash matches
			if ($calculatedHash !== $hash)
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_INVALID_HASH'));
			}

			// Get the data
			$data = (object) json_decode($encodeData, true);

			if (!isset($data->guid) || strlen($data->guid) <> 36)
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_INVALID_GUID'));
			}

			switch ($data->task)
			{
				case 'profile.delete':
					$this->processDelete($data->guid);
					break;
				case 'profile.save':
					$this->processIdentity($data->guid, $data);
					break;
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
	 * Creates a mapping for the fields used to create a user
	 *
	 * @param   string  $authorizationSource  The alias of the IDP profile configured in RO SSO
	 * @param   array   $attributes           The attributes received from the IDP
	 * @param   array   &$userFields          The list of user fields to fill
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onProcessUserGetFieldMap(string $authorizationSource, array $attributes, array &$userFields = array()): void
	{
		try
		{
			$profile = json_decode($attributes['profile'][0]);

			if (empty($profile[0]))
			{
				throw new InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_NO_PROFILE_DATA_PROVIDED'));
			}

			$data        = $profile[0];
			$data->email = $attributes['emailaddress'][0];
			$data->name  = $attributes['name'][0];

			$this->processIdentity($attributes['guid'][0], $data);
		}
		catch (Exception $exception)
		{
			echo $exception->getMessage();
		}
	}

	/**
	 * Method to process the Joomla Identity data
	 *
	 * @param   string  $guid  The User ID
	 * @param   object  $data  Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function processIdentity($guid, $data)
	{
		try
		{
			// Update the Joomla user
			$this->updateJoomlaUser($guid, $data->name, $data->email);

			// Get Joomla user ID
			$userId = (int) UserHelper::getUserId($guid);

			// Plugin trigger onProcessIdentity for site specific processing
			\JEventDispatcher::getInstance()->trigger('onProcessIdentity', array($userId, $guid, $data));
		}
		catch (Exception $exception)
		{
			echo $exception->getMessage();
		}
	}

	/**
	 * Method to process the Joomla Identity data
	 *
	 * @param   string  $guid  The User ID
	 * @param   object  $data  Object containing user data
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function processDelete($guid, $data)
	{
		try
		{
			// Get Joomla user ID
			$userId = (int) UserHelper::getUserId($guid);

			// Update the Joomla user
			$this->updateJoomlaUser($guid, bin2hex(random_bytes(12)), 'UserID' . $userId . 'removed@email.invalid');

			// Plugin trigger onProcessIdentity for site specific processing
			\JEventDispatcher::getInstance()->trigger('onProcessDelete', array($userId, $guid, $data));
		}
		catch (Exception $exception)
		{
			echo $exception->getMessage();
		}
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
			$this->_subject->setError(Text::_('PLG_SYSTEM_JOOMLAIDENTITY_NO_USERID'));

			return false;
		}

		// Generate a GUID
		require_once JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php';
		$helper = new IdentityHelper;
		$guid   = $helper->generateGuid();

		// Add the user to the profiles table
		$db = $this->db;
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

		// Get the general consents for this site
		if (!$this->params->get('isIdentityProvider'))
		{
			return true;
		}

		// Require the helper
		require_once JPATH_ADMINISTRATOR . '/components/com_identity/helpers/identity.php';
		$helper = new IdentityHelper;

		$consents = $helper->loadSiteConsents(1);

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
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was succesfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onUserBeforeDelete($user, $success, $msg)
	{
		return false;
	}
}
