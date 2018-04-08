<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentity
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
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
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Triggered before Joomla! renders the page
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRoute()
	{
		// Run on frontend only
		if ($this->app->isClient('administrator'))
		{
			return;
		}

		// Check for Joomla Identity
		$joomlaIdentity = $this->app->input->getCmd('joomlaidentity');

		// Only continue for Joomla Identity
		if (!isset($joomlaIdentity))
		{
			return;
		}

		// Get the data from the payload
		$guid    = $this->app->input->json->getString('guid');
		$consent = $this->app->input->json->getInt('consent');
		$data    = (object) $this->app->input->json->get('data', array(), 'array');

		if ($guid && $data)
		{
			$this->processIdentity($guid, $consent, $data);

			$this->app->close();
		}
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
	 * @since   1.0
	 */
	protected function processIdentity($guid, $consent, $data)
	{
		// Set anonymize name if no consent is provided
		if ($consent == 0)
		{
			$data->name  = 'Guest ' . substr($guid, 0, 8);
			$data->email = substr($guid, 0, 8) . '@identity.joomla.org';
		}

		// Update the Joomla user
		$this->updateJoomlaUser($guid, $data->name, $data->email);

		// Get Joomla user ID
		$userId = (int) JUserHelper::getUserId($guid);

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
	 * @since   1.0
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
	 * @since   1.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		// Check we are manipulating a valid form.
		if (!in_array($form->getName(), array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
		{
			return true;
		}

		// Disable editing the name, username, email, password and requireReset fields
		$form->setFieldAttribute('name', 'readonly', 'readonly');
		$form->setFieldAttribute('username', 'readonly', 'readonly');
		$form->setFieldAttribute('email', 'readonly', 'readonly');
		$form->setFieldAttribute('password', 'readonly', 'readonly');
		$form->setFieldAttribute('password2', 'readonly', 'readonly');
		$form->setFieldAttribute('requireReset', 'readonly', 'readonly');

		return true;
	}
}
